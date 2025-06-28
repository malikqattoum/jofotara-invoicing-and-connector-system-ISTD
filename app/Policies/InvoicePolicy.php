<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InvoicePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own invoices
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // Users can view invoices they created or invoices in their organization
        return $invoice->user_id === $user->id ||
               $invoice->vendor_id === $user->id ||
               ($invoice->organization_id && $invoice->organization_id === $user->organization_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create invoices
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // Users can only update their own invoices and only if they're in draft status
        return ($invoice->user_id === $user->id || $invoice->vendor_id === $user->id) &&
               in_array($invoice->status, ['draft', 'pending']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // Users can only delete their own draft invoices
        return ($invoice->user_id === $user->id || $invoice->vendor_id === $user->id) &&
               $invoice->status === 'draft';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Invoice $invoice): bool
    {
        return $invoice->user_id === $user->id || $invoice->vendor_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Invoice $invoice): bool
    {
        // Only allow force delete for admin users or invoice owners in specific cases
        return $user->is_admin ||
               (($invoice->user_id === $user->id || $invoice->vendor_id === $user->id) &&
                $invoice->status === 'draft');
    }

    /**
     * Determine whether the user can download the invoice.
     */
    public function download(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice);
    }

    /**
     * Determine whether the user can print the invoice.
     */
    public function print(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice);
    }
}
