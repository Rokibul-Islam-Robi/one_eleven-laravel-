<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerDocument;
use App\Models\SellerLog;
use App\Notifications\SellerAccountStatusNotification;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

final class SellersController extends Controller
{
    public static function mod_sellers(string $action, int $id): void
    {
        switch ($action) {
            case 'view':
                require_module('sellers', 'view');
                self::sellers_view(self::findSeller($id));
                break;
            case 'approve':
                require_module('sellers', 'edit');
                self::approve(self::findSeller($id));
                break;
            case 'activate':
                require_module('sellers', 'edit');
                self::activate(self::findSeller($id));
                break;
            case 'reject':
                require_module('sellers', 'edit');
                self::reject(self::findSeller($id));
                break;
            case 'suspend':
                require_module('sellers', 'edit');
                self::suspend(self::findSeller($id));
                break;
            case 'deactivate':
                require_module('sellers', 'edit');
                self::deactivate(self::findSeller($id));
                break;
            case 'document':
                require_module('sellers', 'view');
                self::document($id);
                break;
            case 'document-approve':
                require_module('sellers', 'edit');
                self::reviewDocument($id, 'approved');
                break;
            case 'document-reject':
                require_module('sellers', 'edit');
                self::reviewDocument($id, 'rejected');
                break;
            default:
                require_module('sellers', 'view');
                self::sellers_list();
        }
    }

    private static function findSeller(int $id): Seller
    {
        $seller = Seller::query()->find($id);
        if (! $seller) {
            adm_status(404);
            render_layout('Seller not found', 'sellers', '<div class="adm-card adm-card-pad"><h3>Seller not found</h3><a class="abtn primary mt-3" href="'.e(aurl('sellers')).'">Back to sellers</a></div>');
        }

        return $seller;
    }

    private static function sellers_list(): void
    {
        $query = Seller::query()->select([
            'id', 'shop_name', 'owner_name', 'email', 'phone', 'status',
            'application_status', 'is_active', 'email_verified_at', 'created_at',
        ]);
        $status = getq('status');
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('application_status', $status);
        }
        $term = getq('q');
        if ($term !== '') {
            $query->where(function ($builder) use ($term) {
                $like = '%'.addcslashes($term, '\\%_').'%';
                $builder->where('shop_name', 'like', $like)
                    ->orWhere('owner_name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }
        $sellers = $query->orderByDesc('created_at')->paginate(25)->withQueryString();
        $html = '<div class="d-flex justify-content-between align-items-center mb-3"><div><h1 class="h3 mb-1">Seller applications</h1><p class="text-muted mb-0">Review applications and manage seller access.</p></div></div>';
        $html .= '<form method="get" action="'.e(aurl('sellers')).'" class="adm-card adm-card-pad mb-3"><div class="row g-2"><div class="col-md-6"><input class="form-control" name="q" value="'.e($term).'" placeholder="Search shop, owner or email"></div><div class="col-md-3"><select class="form-select" name="status"><option value="">All application states</option>';
        foreach (['pending', 'approved', 'rejected'] as $option) {
            $html .= '<option value="'.$option.'"'.($status === $option ? ' selected' : '').'>'.ucfirst($option).'</option>';
        }
        $html .= '</select></div><div class="col-md-3"><button class="abtn primary" type="submit">Filter</button></div></div></form>';
        $html .= '<div class="adm-card table-responsive"><table class="table mb-0"><thead><tr><th>Shop</th><th>Owner</th><th>Contact</th><th>Application</th><th>Access</th><th>Email</th><th></th></tr></thead><tbody>';
        foreach ($sellers as $seller) {
            $contact = '<div>'.e($seller->email).'</div><small>'.e($seller->phone).'</small>';
            $email = $seller->email_verified_at ? 'Verified' : 'Unverified';
            $access = $seller->status.($seller->is_active ? '' : ' / inactive');
            $html .= '<tr><td>'.e($seller->shop_name).'</td><td>'.e($seller->owner_name).'</td><td>'.$contact.'</td><td>'.e(ucfirst($seller->application_status)).'</td><td>'.e(ucfirst($access)).'</td><td>'.$email.'</td><td><a class="abtn sm" href="'.e(aurl('sellers/view/'.$seller->id)).'">Review</a></td></tr>';
        }
        if ($sellers->isEmpty()) {
            $html .= '<tr><td colspan="7" class="text-center text-muted py-4">No seller applications found.</td></tr>';
        }
        $html .= '</tbody></table></div>'.$sellers->links()->toHtml();
        render_layout('Seller applications', 'sellers', $html, true);
    }

    private static function sellers_view(Seller $seller): void
    {
        $documents = $seller->sellerDocuments()->latest('created_at')->get();
        $pendingDocuments = $documents->where('status', 'pending')->count();
        $maskedAccount = self::mask((string) $seller->payout_account);
        $html = '<div class="d-flex justify-content-between align-items-center mb-3"><div><h1 class="h3 mb-1">'.e($seller->shop_name).'</h1><p class="text-muted mb-0">Application #'.(int) $seller->id.'</p></div><a class="abtn" href="'.e(aurl('sellers')).'">Back</a></div>';
        $html .= '<div class="adm-card adm-card-pad"><div class="row g-3">';
        foreach ([
            'Owner' => $seller->owner_name,
            'Legal business' => $seller->legal_business_name ?: '—',
            'Category' => $seller->category ?: '—',
            'Email' => $seller->email.($seller->email_verified_at ? ' (verified)' : ' (unverified)'),
            'Phone' => $seller->phone.($seller->phone_verified_at ? ' (verified)' : ' (not verified)'),
            'Tax identifier' => $seller->tax_identifier ?: '—',
            'Trade license' => $seller->trade_license ?: '—',
            'Drug license' => $seller->drug_license ?: '—',
            'Payout method' => $seller->payout_method ?: '—',
            'Payout account' => $maskedAccount,
            'Payout verification' => $seller->payout_verified ? 'Verified' : 'Not verified',
            'Application status' => $seller->application_status,
            'Account status' => $seller->status.($seller->is_active ? '' : ' (inactive)'),
            'Submitted' => $seller->application_submitted_at ?: '—',
            'Approved' => $seller->approved_at ?: '—',
        ] as $label => $value) {
            $html .= '<div class="col-md-4"><small class="text-muted d-block">'.e($label).'</small><strong>'.e((string) $value).'</strong></div>';
        }
        $html .= '<div class="col-md-6"><small class="text-muted d-block">Pickup address</small>'.nl2br(e((string) $seller->pickup_address)).'</div><div class="col-md-6"><small class="text-muted d-block">Return address</small>'.nl2br(e((string) $seller->return_address)).'</div><div class="col-12"><small class="text-muted d-block">Description</small>'.nl2br(e((string) $seller->description)).'</div></div></div>';

        $html .= '<div class="adm-card adm-card-pad mt-3"><h2 class="h5">Supporting documents</h2>';
        if ($documents->isEmpty()) {
            $html .= '<p class="text-muted mb-0">No supporting documents supplied.</p>';
        } else {
            $html .= '<div class="table-responsive"><table class="table mb-0"><thead><tr><th>Type</th><th>File</th><th>Status</th><th>Review</th></tr></thead><tbody>';
            foreach ($documents as $document) {
                $url = aurl('sellers/document/'.$document->id);
                $html .= '<tr><td>'.e(ucwords(str_replace('_', ' ', $document->document_type))).'</td><td><a href="'.e($url).'">'.e($document->original_name).'</a></td><td>'.e(ucfirst($document->status)).'</td><td>';
                if ($document->status === 'pending') {
                    $html .= self::postForm('sellers/document-approve/'.$document->id, 'Approve document', 'btn btn-sm btn-success')
                        .'<form method="post" action="'.e(aurl('sellers/document-reject/'.$document->id)).'" class="d-inline-flex gap-1">'.csrf_field().'<input name="reason" maxlength="255" required placeholder="Reason"><button class="btn btn-sm btn-outline-danger" type="submit">Reject</button></form>';
                } else {
                    $html .= e($document->review_reason ?: 'Reviewed');
                }
                $html .= '</td></tr>';
            }
            $html .= '</tbody></table></div>';
        }
        $html .= '</div>';

        $html .= '<div class="adm-card adm-card-pad mt-3"><h2 class="h5">Application actions</h2><p>Application: '.e($seller->application_status).' · Access: '.e($seller->status).($seller->is_active ? '' : ' / inactive').'</p>';
        if ($seller->application_status === 'pending' && $seller->email_verified_at && $pendingDocuments === 0) {
            $html .= self::postForm('sellers/approve/'.$seller->id, 'Approve application', 'btn btn-success');
        } elseif ($seller->application_status === 'pending' && $pendingDocuments > 0) {
            $html .= '<p class="text-warning">Review all pending documents before approving this application.</p>';
        } elseif ($seller->application_status === 'pending') {
            $html .= '<p class="text-warning">Verify the seller email before approving.</p>';
        }
        if ($seller->application_status === 'approved' && $seller->status !== 'active') {
            $html .= self::postForm('sellers/activate/'.$seller->id, 'Activate seller', 'btn btn-success');
        } elseif ($seller->application_status === 'approved' && $seller->status === 'active' && ! $seller->is_active) {
            $html .= self::postForm('sellers/activate/'.$seller->id, 'Reactivate seller', 'btn btn-success');
        }
        if (in_array($seller->application_status, ['pending', 'approved'], true) && $seller->status !== 'active') {
            $html .= '<form method="post" action="'.e(aurl('sellers/reject/'.$seller->id)).'" class="d-inline-flex gap-2 ms-2">'.csrf_field().'<input name="reason" maxlength="255" required placeholder="Reason for rejection"><button class="btn btn-outline-danger" type="submit">Reject</button></form>';
        }
        if ($seller->status === 'active' && $seller->is_active) {
            $html .= '<form method="post" action="'.e(aurl('sellers/suspend/'.$seller->id)).'" class="d-inline-flex gap-2 ms-2">'.csrf_field().'<input name="reason" maxlength="255" required placeholder="Reason for suspension"><button class="btn btn-outline-danger" type="submit">Suspend</button></form>';
            $html .= self::postForm('sellers/deactivate/'.$seller->id, 'Deactivate access', 'btn btn-outline-warning');
        }
        if ($seller->status_reason) {
            $html .= '<p class="mt-3 mb-0"><strong>Latest reason:</strong> '.e($seller->status_reason).'</p>';
        }
        $html .= '</div>';
        render_layout('Review seller', 'sellers', $html, true);
    }

    private static function approve(Seller $seller): void
    {
        if ($seller->application_status !== 'pending' || ! $seller->email_verified_at) {
            flash_redirect('error', 'Only a pending, email-verified application can be approved.', 'sellers/view/'.$seller->id);
        }
        if ($seller->sellerDocuments()->where('status', 'pending')->exists()) {
            flash_redirect('error', 'Review all pending seller documents before approving this application.', 'sellers/view/'.$seller->id);
        }
        self::changeSeller($seller, 'approved', null);
    }

    private static function activate(Seller $seller): void
    {
        if ($seller->application_status !== 'approved' || ! $seller->email_verified_at) {
            flash_redirect('error', 'Approve the application and verify its email before activation.', 'sellers/view/'.$seller->id);
        }
        self::changeSeller($seller, 'activated', null);
    }

    private static function reject(Seller $seller): void
    {
        if (! in_array($seller->application_status, ['pending', 'approved'], true) || $seller->status === 'active') {
            flash_redirect('error', 'This seller application cannot be rejected in its current state.', 'sellers/view/'.$seller->id);
        }
        $reason = trim((string) request()->validate(['reason' => ['required', 'string', 'max:255']])['reason']);
        self::changeSeller($seller, 'rejected', $reason);
    }

    private static function suspend(Seller $seller): void
    {
        if ($seller->status !== 'active' || ! $seller->is_active) {
            flash_redirect('error', 'Only an active seller can be suspended.', 'sellers/view/'.$seller->id);
        }
        $reason = trim((string) request()->validate(['reason' => ['required', 'string', 'max:255']])['reason']);
        self::changeSeller($seller, 'suspended', $reason);
    }

    private static function deactivate(Seller $seller): void
    {
        if ($seller->status !== 'active' || ! $seller->is_active) {
            flash_redirect('error', 'Only an active seller can be deactivated.', 'sellers/view/'.$seller->id);
        }
        self::changeSeller($seller, 'inactive', 'Seller access deactivated by administrator.');
    }

    private static function changeSeller(Seller $seller, string $event, ?string $reason): void
    {
        $adminId = (int) (session('admin_user.id') ?? 0);
        DB::transaction(function () use ($seller, $event, $reason, $adminId) {
            $locked = Seller::query()->lockForUpdate()->findOrFail($seller->id);
            $allowed = match ($event) {
                'approved' => $locked->application_status === 'pending'
                    && $locked->email_verified_at !== null
                    && ! $locked->sellerDocuments()->where('status', 'pending')->exists(),
                'activated' => $locked->application_status === 'approved'
                    && $locked->email_verified_at !== null
                    && ($locked->status !== 'active' || ! $locked->is_active),
                'rejected' => in_array($locked->application_status, ['pending', 'approved'], true) && $locked->status !== 'active',
                'suspended', 'inactive' => $locked->status === 'active' && $locked->is_active,
                default => false,
            };
            if (! $allowed) {
                throw new ConflictHttpException('The seller status changed. Reload the page before trying again.');
            }
            $now = now();
            $values = ['updated_at' => $now];
            switch ($event) {
                case 'approved':
                    $values += ['application_status' => 'approved', 'approved_by' => $adminId, 'approved_at' => $now];
                    break;
                case 'activated':
                    $values += ['status' => 'active', 'is_active' => true, 'activated_by' => $adminId, 'activated_at' => $now, 'status_reason' => null];
                    break;
                case 'rejected':
                    $values += ['application_status' => 'rejected', 'status' => 'rejected', 'status_reason' => $reason, 'rejected_by' => $adminId, 'rejected_at' => $now, 'is_active' => false];
                    break;
                case 'suspended':
                    $values += ['status' => 'suspended', 'status_reason' => $reason, 'suspended_by' => $adminId, 'suspended_at' => $now, 'is_active' => false];
                    break;
                case 'inactive':
                    $values += ['is_active' => false, 'status_reason' => $reason];
                    break;
            }
            $locked->forceFill($values)->save();
            self::audit($locked, $event, $adminId, $reason);
        });

        if (in_array($event, ['approved', 'activated', 'rejected', 'suspended', 'inactive'], true)) {
            try {
                $seller->notify(new SellerAccountStatusNotification($event, $reason));
            } catch (Throwable $exception) {
                report($exception);
                flash('warning', 'The status was saved, but the seller notification could not be sent.');
            }
        }
        log_action('seller.'.$event, 'seller', (int) $seller->id, $reason ? 'Reason recorded.' : null);
        flash('success', match ($event) {
            'approved' => 'Seller application approved.',
            'activated' => 'Seller account activated.',
            'rejected' => 'Seller application rejected.',
            'suspended' => 'Seller account suspended.',
            default => 'Seller access deactivated.',
        });
        aredirect('sellers/view/'.$seller->id);
    }

    private static function reviewDocument(int $id, string $status): void
    {
        $document = SellerDocument::query()->find($id);
        if (! $document) {
            adm_status(404);
            render_layout('Document not found', 'sellers', '<div class="adm-card adm-card-pad"><h3>Seller document not found</h3><a href="'.e(aurl('sellers')).'">Back to sellers</a></div>');
        }
        if ($document->status !== 'pending') {
            flash_redirect('error', 'This document has already been reviewed.', 'sellers/view/'.$document->seller_id);
        }
        $reason = $status === 'rejected'
            ? trim((string) request()->validate(['reason' => ['required', 'string', 'max:255']])['reason'])
            : null;
        $adminId = (int) (session('admin_user.id') ?? 0);
        DB::transaction(function () use ($document, $status, $reason, $adminId) {
            $lockedDocument = SellerDocument::query()->lockForUpdate()->findOrFail($document->id);
            if ($lockedDocument->status !== 'pending') {
                throw new ConflictHttpException('The seller document has already been reviewed.');
            }
            $lockedDocument->forceFill([
                'status' => $status,
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
                'review_reason' => $reason,
            ])->save();
            $seller = Seller::query()->findOrFail($document->seller_id);
            self::audit($seller, 'document.'.$status, $adminId, 'Document #'.$document->id.($reason ? ' rejected with reason.' : ' approved.'));
        });
        flash('success', 'Seller document '.$status.'.');
        aredirect('sellers/view/'.$document->seller_id);
    }

    private static function document(int $id): never
    {
        $document = SellerDocument::query()->findOrFail($id);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($document->storage_path), 404);
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($document->original_name)) ?: 'seller-document';

        $response = response()->file($disk->path($document->storage_path), [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        throw new HttpResponseException($response);
    }

    private static function audit(Seller $seller, string $action, int $adminId, ?string $reason): void
    {
        SellerLog::query()->create([
            'seller_id' => $seller->id,
            'actor_type' => 'admin',
            'actor_id' => $adminId,
            'action' => 'admin.'.$action,
            'detail' => $reason ? mb_substr('Reason recorded: '.$reason, 0, 255) : null,
            'ip' => substr((string) request()->ip(), 0, 45),
            'created_at' => now(),
        ]);
    }

    private static function mask(string $account): string
    {
        if ($account === '') {
            return 'Not supplied';
        }

        return str_repeat('*', max(0, mb_strlen($account) - 4)).mb_substr($account, -4);
    }

    private static function postForm(string $path, string $label, string $class): string
    {
        return '<form method="post" action="'.e(aurl($path)).'" class="d-inline">'.csrf_field().'<button class="'.e($class).'" type="submit">'.e($label).'</button></form> ';
    }
}
