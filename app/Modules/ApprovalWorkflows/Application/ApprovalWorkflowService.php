<?php

namespace App\Modules\ApprovalWorkflows\Application;

use App\Core\Services\AuditService;
use App\Modules\ApprovalWorkflows\Infrastructure\Models\Approval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ApprovalWorkflowService
{
    public function __construct(
        protected AuditService $audit
    ) {}

    private function isApprovalsUniqueViolation(QueryException $e): bool
    {
        $code = (string) $e->getCode();
        $message = strtolower($e->getMessage());

        // MySQL 1062 reports SQLSTATE 23000. We also pin to our known unique constraint name.
        return $code === '23000' && str_contains($message, 'approvals_one_active_per_type_uq');
    }

    /**
     * Request approval for a given approvable entity.
     *
     * Idempotency: if an approval already exists for (approvable,type), it is returned as-is.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public function requestApproval(
        Model $approvable,
        string $approvalType,
        int $requestedBy,
        ?string $comments = null,
        ?array $metadata = null
    ): Approval {
        return DB::transaction(function () use ($approvable, $approvalType, $requestedBy, $comments, $metadata) {
            /** @var Approval|null $existing */
            $existing = Approval::query()
                ->where('approvable_type', $approvable->getMorphClass())
                ->where('approvable_id', $approvable->getKey())
                ->where('approval_type', $approvalType)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            try {
                $created = Approval::create([
                    'approvable_type' => $approvable->getMorphClass(),
                    'approvable_id' => $approvable->getKey(),
                    'approval_type' => $approvalType,
                    'status' => Approval::STATUS_PENDING,
                    'requested_by' => $requestedBy,
                    'requested_at' => now(),
                    'comments' => $comments,
                    'metadata' => $metadata,
                ]);

                $this->audit->logFinancial(
                    description: 'Approval requested',
                    subject: $created,
                    properties: [
                        'approval_id' => $created->id,
                        'approval_type' => $created->approval_type,
                        'old_status' => null,
                        'new_status' => $created->status,
                        'approvable_type' => $created->approvable_type,
                        'approvable_id' => $created->approvable_id,
                        'requested_by' => $requestedBy,
                        'comments' => $comments,
                        'metadata' => $metadata,
                    ],
                    event: 'approval.requested'
                );

                return $created;
            } catch (QueryException $e) {
                if ($this->isApprovalsUniqueViolation($e)) {
                    /** @var Approval|null $raceExisting */
                    $raceExisting = Approval::query()
                        ->where('approvable_type', $approvable->getMorphClass())
                        ->where('approvable_id', $approvable->getKey())
                        ->where('approval_type', $approvalType)
                        ->first();

                    if ($raceExisting) {
                        return $raceExisting;
                    }
                }

                throw $e;
            }
        });
    }

    public function approve(Approval $approval, int $approvedBy, ?string $comments = null): Approval
    {
        return DB::transaction(function () use ($approval, $approvedBy, $comments) {
            /** @var Approval $locked */
            $locked = Approval::query()->whereKey($approval->id)->lockForUpdate()->firstOrFail();

            if ($locked->isApproved()) {
                return $locked;
            }

            if ($locked->isRejected() || $locked->isCancelled()) {
                throw new InvalidArgumentException('Only pending approvals can be approved.');
            }

            $locked->update([
                'status' => Approval::STATUS_APPROVED,
                'approved_by' => $approvedBy,
                'approved_at' => now(),
                'comments' => $comments ?? $locked->comments,
            ]);

            $this->audit->logFinancial(
                description: 'Approval approved',
                subject: $locked,
                properties: [
                    'approval_id' => $locked->id,
                    'approval_type' => $locked->approval_type,
                    'old_status' => Approval::STATUS_PENDING,
                    'new_status' => $locked->status,
                    'approvable_type' => $locked->approvable_type,
                    'approvable_id' => $locked->approvable_id,
                    'approved_by' => $approvedBy,
                    'comments' => $comments,
                ],
                event: 'approval.approved'
            );

            return $locked;
        });
    }

    public function reject(Approval $approval, int $rejectedBy, ?string $comments = null): Approval
    {
        return DB::transaction(function () use ($approval, $rejectedBy, $comments) {
            /** @var Approval $locked */
            $locked = Approval::query()->whereKey($approval->id)->lockForUpdate()->firstOrFail();

            if ($locked->isRejected()) {
                return $locked;
            }

            if ($locked->isApproved() || $locked->isCancelled()) {
                throw new InvalidArgumentException('Only pending approvals can be rejected.');
            }

            $locked->update([
                'status' => Approval::STATUS_REJECTED,
                'rejected_by' => $rejectedBy,
                'rejected_at' => now(),
                'comments' => $comments ?? $locked->comments,
            ]);

            $this->audit->logFinancial(
                description: 'Approval rejected',
                subject: $locked,
                properties: [
                    'approval_id' => $locked->id,
                    'approval_type' => $locked->approval_type,
                    'old_status' => Approval::STATUS_PENDING,
                    'new_status' => $locked->status,
                    'approvable_type' => $locked->approvable_type,
                    'approvable_id' => $locked->approvable_id,
                    'rejected_by' => $rejectedBy,
                    'comments' => $comments,
                ],
                event: 'approval.rejected'
            );

            return $locked;
        });
    }

    public function cancel(Approval $approval, int $cancelledBy, ?string $comments = null): Approval
    {
        return DB::transaction(function () use ($approval, $cancelledBy, $comments) {
            /** @var Approval $locked */
            $locked = Approval::query()->whereKey($approval->id)->lockForUpdate()->firstOrFail();

            if ($locked->isCancelled()) {
                return $locked;
            }

            if ($locked->isApproved()) {
                throw new InvalidArgumentException('Approved approvals cannot be cancelled.');
            }

            $locked->update([
                'status' => Approval::STATUS_CANCELLED,
                'comments' => $comments ?? $locked->comments,
                // cancelled_by/cancelled_at omitted for now; keep schema minimal.
            ]);

            $this->audit->logFinancial(
                description: 'Approval cancelled',
                subject: $locked,
                properties: [
                    'approval_id' => $locked->id,
                    'approval_type' => $locked->approval_type,
                    'old_status' => Approval::STATUS_PENDING,
                    'new_status' => $locked->status,
                    'approvable_type' => $locked->approvable_type,
                    'approvable_id' => $locked->approvable_id,
                    'cancelled_by' => $cancelledBy,
                    'comments' => $comments,
                ],
                event: 'approval.cancelled'
            );

            return $locked;
        });
    }
}

