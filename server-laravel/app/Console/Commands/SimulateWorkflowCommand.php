<?php

namespace App\Console\Commands;

use App\Models\ActionLibrary;
use App\Models\Document;
use App\Models\DocumentRecipient;
use App\Models\DocumentTransaction;
use App\Models\DocumentTransactionLog;
use App\Models\OfficeLibrary;
use App\Models\User;
use App\Services\DocumentStatusService;
use App\Services\TransactionStatusService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SimulateWorkflowCommand extends Command
{
    protected $signature = 'simulate:workflow 
                            {--routing=single : Routing type: single, multiple, sequential}
                            {--action=random : Action type: random, fa, fi, or specific action name}
                            {--recipients=1 : Number of recipients (for multiple/sequential)}
                            {--include-forward : Include a forward action in the flow}
                            {--include-return : Include a return to sender action}
                            {--close : Close the document after completion}
                            {--verbose-steps : Show detailed step-by-step output}';

    protected $description = 'Simulate a complete document workflow from profiling to completion with smart decision-making';

    private User $originUser;
    private array $recipientUsers = [];
    private string $timestamp;
    private bool $verboseSteps;
    private int $minutesElapsed = 0;

    public function handle(): int
    {
        $this->timestamp = now()->format('Ymd-His');
        $this->verboseSteps = $this->option('verbose-steps');
        $this->minutesElapsed = 0;

        $this->info("\n╔══════════════════════════════════════════════════════════════╗");
        $this->info("║           DOCUMENT WORKFLOW SIMULATION                       ║");
        $this->info("╚══════════════════════════════════════════════════════════════╝\n");

        // Setup
        if (!$this->setupEnvironment()) {
            return 1;
        }

        try {
            DB::transaction(function () {
                $this->runWorkflow();
            });

            $this->newLine();
            $this->info("✓ Workflow simulation completed successfully!");
            return 0;

        } catch (\Exception $e) {
            $this->error("✗ Simulation failed: " . $e->getMessage());
            if ($this->verboseSteps) {
                $this->error($e->getTraceAsString());
            }
            return 1;
        }
    }

    private function setupEnvironment(): bool
    {
        $this->step("Setting up environment...");

        // Get offices with users
        $offices = OfficeLibrary::whereHas('users')->get();

        if ($offices->count() < 2) {
            $this->error("Need at least 2 offices with users. Found: {$offices->count()}");
            return false;
        }

        // Origin office
        $originOffice = $offices->first();
        $this->originUser = User::where('office_id', $originOffice->id)->first();

        // Recipient offices
        $recipientCount = max(1, min((int)$this->option('recipients'), $offices->count() - 1));
        $recipientOffices = $offices->slice(1, $recipientCount);

        foreach ($recipientOffices as $office) {
            $user = User::where('office_id', $office->id)->first();
            if ($user) {
                $this->recipientUsers[] = $user;
            }
        }

        // Fill remaining slots if needed
        while (count($this->recipientUsers) < $recipientCount && count($this->recipientUsers) > 0) {
            $this->recipientUsers[] = $this->recipientUsers[0];
        }

        $this->detail("  Origin: {$this->originUser->office_name}");
        foreach ($this->recipientUsers as $i => $user) {
            $this->detail("  Recipient " . ($i + 1) . ": {$user->office_name}");
        }

        return true;
    }

    private function runWorkflow(): void
    {
        $routing = strtolower($this->option('routing'));
        $actionOption = $this->option('action');

        // Step 1: Determine action type
        $actionType = $this->determineActionType($actionOption);
        $actionLib = ActionLibrary::where('name', $actionType)->first();
        $isFI = $actionLib?->type === 'FI';

        $this->step("Action Type: {$actionType} (" . ($isFI ? 'FI' : 'FA') . ")");
        $this->step("Routing: " . ucfirst($routing));

        // Step 2: Profile document
        $doc = $this->profileDocument($actionType);
        $this->step("📄 Document profiled: {$doc->document_no}");

        // Step 3: Create transaction with recipients
        $trx = $this->createTransactionWithRecipients($doc, $routing, $actionType);
        $this->step("📋 Transaction created: {$trx->transaction_no}");

        // Step 4: Release document
        $this->releaseDocument($doc, $trx);
        $this->step("📤 Document released");

        // Step 5: Process each recipient based on routing
        $this->processRecipients($doc, $trx, $routing, $isFI);

        // Step 6: Final status check
        $trx->refresh();
        $doc->refresh();
        
        $this->newLine();
        $this->info("═══════════════════════════════════════════════════════════════");
        $this->info("FINAL STATUS:");
        $this->info("  Transaction: {$trx->status}");
        $this->info("  Document: {$doc->status}");
        $this->info("═══════════════════════════════════════════════════════════════");

        // Step 7: Close if requested
        if ($this->option('close') && $doc->status === 'Completed') {
            $this->closeDocument($doc, $trx);
            $this->step("🔒 Document closed");
        }
    }

    private function determineActionType(string $option): string
    {
        if ($option === 'random') {
            $actions = ActionLibrary::where('isActive', true)->pluck('name')->toArray();
            return $actions[array_rand($actions)] ?? 'Appropriate Action';
        }

        if ($option === 'fa') {
            $faActions = ActionLibrary::where('type', 'FA')->where('isActive', true)->pluck('name')->toArray();
            return $faActions[array_rand($faActions)] ?? 'Appropriate Action';
        }

        if ($option === 'fi') {
            $fiActions = ActionLibrary::where('type', 'FI')->where('isActive', true)->pluck('name')->toArray();
            return $fiActions[array_rand($fiActions)] ?? 'Your Information';
        }

        // Specific action name
        $action = ActionLibrary::where('name', $option)->first();
        return $action ? $action->name : 'Appropriate Action';
    }

    private function profileDocument(string $actionType): Document
    {
        $docNo = "SIM-{$this->timestamp}-" . Str::random(6);
        
        return Document::create([
            'document_no' => $docNo,
            'subject' => "Simulated Document - {$actionType}",
            'document_type' => 'Memorandum',
            'action_type' => $actionType,
            'origin_type' => 'Internal',
            'remarks' => 'Auto-generated by workflow simulation',
            'status' => 'Draft',
            'office_id' => $this->originUser->office_id,
            'office_name' => $this->originUser->office_name,
            'created_by_id' => $this->originUser->id,
            'created_by_name' => $this->originUser->fullName(),
        ]);
    }

    private function createTransactionWithRecipients(Document $doc, string $routing, string $actionType): DocumentTransaction
    {
        $trxNo = "TRX-{$this->timestamp}-" . Str::random(6);
        
        $trx = DocumentTransaction::create([
            'transaction_no' => $trxNo,
            'transaction_type' => 'Default',
            'routing' => ucfirst($routing),
            'document_no' => $doc->document_no,
            'document_type' => $doc->document_type,
            'action_type' => $actionType,
            'origin_type' => $doc->origin_type,
            'subject' => $doc->subject,
            'remarks' => $doc->remarks,
            'status' => 'Draft',
            'urgency_level' => 'High',
            'due_date' => now()->addDays(3),
            'office_id' => $this->originUser->office_id,
            'office_name' => $this->originUser->office_name,
            'created_by_id' => $this->originUser->id,
            'created_by_name' => $this->originUser->fullName(),
            'isActive' => true,
        ]);

        // Add recipients based on routing
        foreach ($this->recipientUsers as $i => $user) {
            $isActive = $routing === 'sequential' ? ($i === 0) : true;
            
            DocumentRecipient::create([
                'transaction_no' => $trx->transaction_no,
                'document_no' => $doc->document_no,
                'office_id' => $user->office_id,
                'office_name' => $user->office_name,
                'recipient_type' => 'default',
                'sequence' => $i + 1,
                'isActive' => $isActive,
                'created_by_id' => $this->originUser->id,
                'created_by_name' => $this->originUser->fullName(),
            ]);

            $this->detail("  + Recipient {$user->office_name} (seq: " . ($i + 1) . ", active: " . ($isActive ? 'yes' : 'no') . ")");
        }

        return $trx;
    }

    private function releaseDocument(Document $doc, DocumentTransaction $trx): void
    {
        $recipientNames = collect($this->recipientUsers)->pluck('office_name')->unique()->implode(', ');
        
        $this->logAction($trx, 'Released', $this->originUser, "Document released to: {$recipientNames}");
        
        $trx->update(['status' => 'Processing']);
        $doc->update(['status' => 'Active']);
    }

    private function processRecipients(Document $doc, DocumentTransaction $trx, string $routing, bool $isFI): void
    {
        $includeForward = $this->option('include-forward');
        $includeReturn = $this->option('include-return');

        $recipients = $trx->recipients()->where('recipient_type', 'default')->orderBy('sequence')->get();
        $totalRecipients = $recipients->count();
        $processedCount = 0;

        foreach ($recipients as $index => $recipient) {
            $user = User::where('office_id', $recipient->office_id)->first();
            if (!$user) continue;

            // For sequential, activate this recipient if not first
            if ($routing === 'sequential' && $index > 0) {
                $recipient->update(['isActive' => true]);
                $this->detail("  → Activated recipient: {$user->office_name}");
            }

            // Skip if not active
            if (!$recipient->fresh()->isActive) {
                continue;
            }

            $processedCount++;
            $this->newLine();
            $this->step("👤 Processing: {$user->office_name} (Recipient {$processedCount}/{$totalRecipients})");

            // Receive
            $this->logAction($trx, 'Received', $user, "Document received by {$user->office_name}");
            $this->detail("  ✓ Received");

            // Decide action
            if ($includeReturn && $processedCount === 1 && $totalRecipients > 1) {
                // Return to sender scenario
                $this->returnToSender($doc, $trx, $recipient, $user);
                break;
            }

            if ($includeForward && $processedCount === 1 && !$isFI) {
                // Forward scenario
                $this->forwardDocument($doc, $trx, $recipient, $user);
                // Original recipient is done, but forwarded transaction is in progress
                TransactionStatusService::evaluate($trx->transaction_no);
                continue;
            }

            // Normal completion flow
            if ($isFI) {
                // FI: Receive is terminal - deactivate and auto-log Done
                $this->logAction($trx, 'Done', $user, "Information noted by {$user->office_name}");
                $recipient->update(['isActive' => false]);
                $this->detail("  ✓ Information noted (FI auto-complete)");
            } else {
                // FA: Mark as Done
                $this->logAction($trx, 'Done', $user, "Action completed by {$user->office_name}");
                $recipient->update(['isActive' => false]);
                $this->detail("  ✓ Marked as Done");
            }

            // Evaluate status after each action
            $status = TransactionStatusService::evaluate($trx->transaction_no);
            $this->detail("  → Transaction status: {$status}");

            // If sequential,activate next
            if ($routing === 'sequential' && $index < $totalRecipients - 1) {
                $nextRecipient = $recipients[$index + 1];
                $nextRecipient->update(['isActive' => true]);
            }
        }
    }

    private function returnToSender(Document $doc, DocumentTransaction $trx, DocumentRecipient $recipient, User $user): void
    {
        $this->logAction(
            $trx, 
            'Returned To Sender', 
            $user, 
            "Document returned to {$this->originUser->office_name}",
            $this->originUser->office_id,
            $this->originUser->office_name,
            'Requires additional information'
        );
        
        $recipient->update(['isActive' => false]);

        // Deactivate all pending recipients
        $trx->recipients()
            ->where('recipient_type', 'default')
            ->where('isActive', true)
            ->update(['isActive' => false]);

        $trx->update(['status' => 'Returned']);
        $doc->update(['status' => 'Returned']);

        $this->detail("  ↩ Returned to sender");
        $this->detail("  → Transaction status: Returned");
    }

    private function forwardDocument(Document $doc, DocumentTransaction $trx, DocumentRecipient $recipient, User $user): void
    {
        // Get a different office to forward to
        $forwardTo = User::where('office_id', '!=', $user->office_id)
            ->where('office_id', '!=', $this->originUser->office_id)
            ->first() ?? $this->originUser;

        $this->logAction(
            $trx, 
            'Forwarded', 
            $user, 
            "Forwarded to {$forwardTo->office_name}",
            $forwardTo->office_id,
            $forwardTo->office_name
        );
        
        $recipient->update(['isActive' => false]);
        $this->detail("  ↪ Forwarded to {$forwardTo->office_name}");

        // Create forwarded transaction
        $forwardTrxNo = "TRX-{$this->timestamp}-FWD-" . Str::random(4);
        
        $forwardTrx = DocumentTransaction::create([
            'transaction_no' => $forwardTrxNo,
            'transaction_type' => 'Forward',
            'parent_transaction_no' => $trx->transaction_no,
            'routing' => 'Single',
            'document_no' => $doc->document_no,
            'document_type' => $doc->document_type,
            'action_type' => $trx->action_type,
            'origin_type' => $doc->origin_type,
            'subject' => $doc->subject,
            'remarks' => 'Forwarded document',
            'status' => 'Draft',
            'urgency_level' => $trx->urgency_level,
            'due_date' => $trx->due_date,
            'office_id' => $user->office_id,
            'office_name' => $user->office_name,
            'created_by_id' => $user->id,
            'created_by_name' => $user->fullName(),
            'isActive' => true,
        ]);

        DocumentRecipient::create([
            'transaction_no' => $forwardTrx->transaction_no,
            'document_no' => $doc->document_no,
            'office_id' => $forwardTo->office_id,
            'office_name' => $forwardTo->office_name,
            'recipient_type' => 'default',
            'sequence' => 1,
            'isActive' => true,
            'created_by_id' => $user->id,
            'created_by_name' => $user->fullName(),
        ]);

        $this->logAction($forwardTrx, 'Released', $user, "Forwarded document released to {$forwardTo->office_name}");
        $forwardTrx->update(['status' => 'Processing']);

        $this->detail("  📋 Forward transaction: {$forwardTrxNo}");

        // Process the forwarded recipient
        $this->logAction($forwardTrx, 'Received', $forwardTo, "Document received by {$forwardTo->office_name}");
        $this->detail("  ✓ Forward recipient received");

        $this->logAction($forwardTrx, 'Done', $forwardTo, "Action completed by {$forwardTo->office_name}");
        $forwardTrx->recipients()->update(['isActive' => false]);
        $this->detail("  ✓ Forward recipient marked as Done");

        TransactionStatusService::evaluate($forwardTrx->transaction_no);
        $this->detail("  → Forward transaction status: {$forwardTrx->fresh()->status}");
    }

    private function closeDocument(Document $doc, DocumentTransaction $trx): void
    {
        $this->logAction($trx, 'Closed', $this->originUser, "Document closed by origin office");
        $doc->update(['status' => 'Closed']);
    }

    private function logAction(
        DocumentTransaction $trx,
        string $status,
        User $user,
        string $activity,
        ?string $routedOfficeId = null,
        ?string $routedOfficeName = null,
        ?string $reason = null
    ): void {
        // Add realistic time gaps between actions
        $gap = match ($status) {
            'Released'           => rand(5, 30),      // 5-30 min after profiling
            'Received'           => rand(30, 480),     // 30 min to 8 hours after release
            'Done'               => rand(60, 1440),    // 1 hour to 1 day after receive
            'Forwarded'          => rand(30, 240),     // 30 min to 4 hours after receive
            'Returned To Sender' => rand(60, 480),     // 1-8 hours after receive
            'Closed'             => rand(10, 120),     // 10 min to 2 hours after completion
            default              => rand(5, 60),
        };
        $this->minutesElapsed += $gap;
        $logTimestamp = now()->subMinutes(max(10080 - $this->minutesElapsed, 0));

        $log = DocumentTransactionLog::create([
            'transaction_no' => $trx->transaction_no,
            'document_no' => $trx->document_no,
            'status' => $status,
            'action_taken' => $trx->action_type,
            'activity' => $activity,
            'remarks' => $trx->remarks,
            'reason' => $reason,
            'assigned_personnel_id' => $user->id,
            'assigned_personnel_name' => $user->fullName(),
            'office_id' => $user->office_id,
            'office_name' => $user->office_name,
            'routed_office_id' => $routedOfficeId,
            'routed_office_name' => $routedOfficeName,
        ]);

        // Override timestamps to simulate realistic time progression
        $log->timestamps = false;
        $log->update([
            'created_at' => $logTimestamp,
            'updated_at' => $logTimestamp,
        ]);
    }

    private function step(string $message): void
    {
        $this->line("▸ {$message}");
    }

    private function detail(string $message): void
    {
        if ($this->verboseSteps) {
            $this->line($message);
        }
    }
}
