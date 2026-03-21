<?php

namespace App\Console\Commands;

use App\Models\ActionLibrary;
use App\Models\Document;
use App\Models\DocumentRecipient;
use App\Models\DocumentTransaction;
use App\Models\DocumentTransactionLog;
use App\Models\OfficeLibrary;
use App\Models\User;
use App\Services\TransactionStatusService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MockTransactionCommand extends Command
{
    protected $signature = 'mock:transaction 
                            {routing=single : Routing type (single, multiple, sequential)}
                            {--scenario= : Specific scenario to run (fa-done, fa-forward, fa-return, fa-reply, fi-receive, all)}
                            {--list : List available scenarios}';

    protected $description = 'Mock transaction scenarios for testing (single, multiple, sequential)';

    private ?User $originUser = null;
    private ?User $recipientUser1 = null;
    private ?User $recipientUser2 = null;
    private ?User $recipientUser3 = null;
    private array $offices = [];

    public function handle(): int
    {
        if ($this->option('list')) {
            $this->listScenarios();
            return Command::SUCCESS;
        }

        $routing = strtolower($this->argument('routing'));

        if (!in_array($routing, ['single', 'multiple', 'sequential'])) {
            $this->error("Invalid routing type: {$routing}. Use: single, multiple, or sequential");
            return Command::FAILURE;
        }

        $this->info("=== Mock Transaction Script ===");
        $this->info("Routing Type: " . ucfirst($routing));
        $this->newLine();

        // Setup test users and offices
        if (!$this->setupTestEnvironment()) {
            return Command::FAILURE;
        }

        $scenario = $this->option('scenario') ?? 'all';

        return match ($routing) {
            'single' => $this->runSingleScenarios($scenario),
            'multiple' => $this->runMultipleScenarios($scenario),
            'sequential' => $this->runSequentialScenarios($scenario),
            default => Command::FAILURE,
        };
    }

    private function listScenarios(): void
    {
        $this->info("=== Available Scenarios ===");
        $this->newLine();

        $this->info("SINGLE Routing:");
        $this->line("  fa-done      - FA: Release → Receive → Mark as Done");
        $this->line("  fa-forward   - FA: Release → Receive → Forward");
        $this->line("  fa-return    - FA: Release → Receive → Return to Sender");
        $this->line("  fa-reply     - FA: Release → Receive → Reply");
        $this->line("  fi-receive   - FI: Release → Receive (auto-complete)");
        $this->line("  all          - Run all single scenarios");
        $this->newLine();

        $this->info("MULTIPLE Routing:");
        $this->line("  fa-all-done     - FA: All recipients mark as done");
        $this->line("  fa-partial-done - FA: Some recipients done, others pending");
        $this->line("  fa-one-return   - FA: One recipient returns");
        $this->line("  fi-all-receive  - FI: All recipients receive");
        $this->line("  all             - Run all multiple scenarios");
        $this->newLine();

        $this->info("SEQUENTIAL Routing:");
        $this->line("  fa-complete     - FA: All recipients complete in sequence");
        $this->line("  fa-mid-return   - FA: Middle recipient returns");
        $this->line("  fa-mid-forward  - FA: Middle recipient forwards");
        $this->line("  fi-complete     - FI: All recipients receive in sequence");
        $this->line("  all             - Run all sequential scenarios");
    }

    private function setupTestEnvironment(): bool
    {
        $this->info("Setting up test environment...");

        // Get offices with users
        $officesWithUsers = OfficeLibrary::whereHas('users')->get();
        $officeCount = $officesWithUsers->count();
        
        if ($officeCount < 2) {
            $this->error("Need at least 2 offices with users in office_libraries table");
            return false;
        }

        // Get test users from different offices
        $this->originUser = User::where('office_id', $officesWithUsers[0]->id)->first();
        $this->recipientUser1 = User::where('office_id', $officesWithUsers[1]->id)->first();
        
        // For multiple/sequential, we may need more users - use same offices if not enough
        $this->recipientUser2 = $officeCount >= 3 
            ? User::where('office_id', $officesWithUsers[2]->id)->first() 
            : $this->recipientUser1;
        $this->recipientUser3 = $officeCount >= 4 
            ? User::where('office_id', $officesWithUsers[3]->id)->first() 
            : $this->recipientUser1;

        if (!$this->originUser || !$this->recipientUser1) {
            $this->error("Need users in at least 2 different offices");
            return false;
        }

        $this->info("  Origin: {$this->originUser->office_name}");
        $this->info("  Recipient 1: {$this->recipientUser1->office_name}");
        if ($officeCount >= 3) {
            $this->info("  Recipient 2: {$this->recipientUser2->office_name}");
        }
        if ($officeCount >= 4) {
            $this->info("  Recipient 3: {$this->recipientUser3->office_name}");
        }
        $this->newLine();

        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SINGLE ROUTING SCENARIOS
    // ─────────────────────────────────────────────────────────────────────────

    private function runSingleScenarios(string $scenario): int
    {
        $this->info("=== SINGLE ROUTING SCENARIOS ===");
        $this->newLine();

        $scenarios = [
            'fa-done' => fn() => $this->singleFaDone(),
            'fa-forward' => fn() => $this->singleFaForward(),
            'fa-return' => fn() => $this->singleFaReturn(),
            'fa-reply' => fn() => $this->singleFaReply(),
            'fi-receive' => fn() => $this->singleFiReceive(),
        ];

        if ($scenario === 'all') {
            foreach ($scenarios as $name => $callback) {
                $this->runScenario($name, $callback);
            }
        } elseif (isset($scenarios[$scenario])) {
            $this->runScenario($scenario, $scenarios[$scenario]);
        } else {
            $this->error("Unknown scenario: {$scenario}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function singleFaDone(): array
    {
        $this->comment("Scenario: FA Single - Release → Receive → Mark as Done");

        // Create document
        $doc = $this->createDocument('FA-SINGLE-DONE', 'Default', 'Appropriate Action');
        $trx = $this->createTransaction($doc, 'Single', 'Appropriate Action');

        // Add single recipient
        $recipient = $this->addRecipient($trx, $this->recipientUser1, 'default', 1);

        // Release
        $this->logAction($trx, 'Released', $this->originUser, "Document released to {$this->recipientUser1->office_name}");
        $trx->update(['status' => 'Processing']);
        $doc->update(['status' => 'Active']);

        // Receive
        $this->logAction($trx, 'Received', $this->recipientUser1, "Document received by {$this->recipientUser1->office_name}");

        // Mark as Done
        $this->logAction($trx, 'Done', $this->recipientUser1, "Marked as done by {$this->recipientUser1->office_name}");
        $recipient->update(['isActive' => false]);

        // Evaluate status
        $status = TransactionStatusService::evaluate($trx->transaction_no);

        $this->info("  Transaction Status: {$status}");
        $this->info("  Document Status: {$doc->fresh()->status}");

        return ['transaction' => $trx, 'document' => $doc, 'status' => $status];
    }

    private function singleFaForward(): array
    {
        $this->comment("Scenario: FA Single - Release → Receive → Forward");

        $doc = $this->createDocument('FA-SINGLE-FORWARD', 'Default', 'Appropriate Action');
        $trx = $this->createTransaction($doc, 'Single', 'Appropriate Action');

        $recipient = $this->addRecipient($trx, $this->recipientUser1, 'default', 1);

        // Release
        $this->logAction($trx, 'Released', $this->originUser, "Document released to {$this->recipientUser1->office_name}");
        $trx->update(['status' => 'Processing']);
        $doc->update(['status' => 'Active']);

        // Receive
        $this->logAction($trx, 'Received', $this->recipientUser1, "Document received by {$this->recipientUser1->office_name}");

        // Forward - creates new transaction
        $this->logAction($trx, 'Forwarded', $this->recipientUser1, 
            "Forwarded to {$this->recipientUser2->office_name}", 
            $this->recipientUser2->office_id, 
            $this->recipientUser2->office_name
        );
        $recipient->update(['isActive' => false]);

        // Create forwarded transaction
        $forwardTrx = $this->createTransaction($doc, 'Single', 'Appropriate Action', 'Forward', $trx->transaction_no);
        $this->addRecipient($forwardTrx, $this->recipientUser2, 'default', 1, $this->recipientUser1);
        
        $this->logAction($forwardTrx, 'Released', $this->recipientUser1, "Forwarded document released to {$this->recipientUser2->office_name}");
        $forwardTrx->update(['status' => 'Processing']);

        // Evaluate original transaction
        $status = TransactionStatusService::evaluate($trx->transaction_no);

        $this->info("  Original Transaction Status: {$status}");
        $this->info("  Forwarded Transaction Status: {$forwardTrx->status}");
        $this->info("  Document Status: {$doc->fresh()->status}");

        return ['transaction' => $trx, 'forwardedTransaction' => $forwardTrx, 'document' => $doc, 'status' => $status];
    }

    private function singleFaReturn(): array
    {
        $this->comment("Scenario: FA Single - Release → Receive → Return to Sender");

        $doc = $this->createDocument('FA-SINGLE-RETURN', 'Default', 'Appropriate Action');
        $trx = $this->createTransaction($doc, 'Single', 'Appropriate Action');

        $recipient = $this->addRecipient($trx, $this->recipientUser1, 'default', 1);

        // Release
        $this->logAction($trx, 'Released', $this->originUser, "Document released to {$this->recipientUser1->office_name}");
        $trx->update(['status' => 'Processing']);
        $doc->update(['status' => 'Active']);

        // Receive
        $this->logAction($trx, 'Received', $this->recipientUser1, "Document received by {$this->recipientUser1->office_name}");

        // Return to Sender
        $this->logAction($trx, 'Returned To Sender', $this->recipientUser1, 
            "Returned to sender by {$this->recipientUser1->office_name}",
            $this->originUser->office_id,
            $this->originUser->office_name,
            'Needs revision'
        );
        $recipient->update(['isActive' => false]);
        $trx->update(['status' => 'Returned']);
        $doc->update(['status' => 'Returned']);

        $this->info("  Transaction Status: {$trx->fresh()->status}");
        $this->info("  Document Status: {$doc->fresh()->status}");

        return ['transaction' => $trx, 'document' => $doc, 'status' => 'Returned'];
    }

    private function singleFaReply(): array
    {
        $this->comment("Scenario: FA Single - Release → Receive → Reply");

        $doc = $this->createDocument('FA-SINGLE-REPLY', 'Default', 'Comment/Reaction/Response');
        $trx = $this->createTransaction($doc, 'Single', 'Comment/Reaction/Response'); // reply_is_terminal = true

        $recipient = $this->addRecipient($trx, $this->recipientUser1, 'default', 1);

        // Release
        $this->logAction($trx, 'Released', $this->originUser, "Document released to {$this->recipientUser1->office_name}");
        $trx->update(['status' => 'Processing']);
        $doc->update(['status' => 'Active']);

        // Receive
        $this->logAction($trx, 'Received', $this->recipientUser1, "Document received by {$this->recipientUser1->office_name}");

        // Reply with reply_is_terminal=true - logs Done on original, then creates new document
        $this->logAction($trx, 'Done', $this->recipientUser1, "Action completed via Reply");
        
        // Reply - creates new document (use short suffix to avoid varchar(50) overflow)
        $replyDoc = $this->createDocument('REPLY-' . substr($doc->document_no, -15), 'Reply', 'Comment/Reaction/Response');
        $replyTrx = $this->createTransaction($replyDoc, 'Single', 'Comment/Reaction/Response', 'Reply');
        $this->addRecipient($replyTrx, $this->originUser, 'default', 1, $this->recipientUser1);

        $this->logAction($replyTrx, 'Released', $this->recipientUser1, "Reply sent to {$this->originUser->office_name}");
        $replyTrx->update(['status' => 'Processing']);
        $replyDoc->update(['status' => 'Active']);

        // Original recipient steps out (reply_is_terminal)
        $recipient->update(['isActive' => false]);

        // Evaluate original transaction
        $status = TransactionStatusService::evaluate($trx->transaction_no);

        $this->info("  Original Transaction Status: {$status}");
        $this->info("  Original Document Status: {$doc->fresh()->status}");
        $this->info("  Reply Document Status: {$replyDoc->status}");

        return ['transaction' => $trx, 'replyTransaction' => $replyTrx, 'document' => $doc, 'replyDocument' => $replyDoc, 'status' => $status];
    }

    private function singleFiReceive(): array
    {
        $this->comment("Scenario: FI Single - Release → Receive (auto-complete)");

        $doc = $this->createDocument('FI-SINGLE-RECEIVE', 'Default', 'Your Information');
        $trx = $this->createTransaction($doc, 'Single', 'Your Information'); // FI type

        $recipient = $this->addRecipient($trx, $this->recipientUser1, 'default', 1);

        // Release
        $this->logAction($trx, 'Released', $this->originUser, "Document released to {$this->recipientUser1->office_name}");
        $trx->update(['status' => 'Processing']);
        $doc->update(['status' => 'Active']);

        // Receive (FI auto-completes)
        $this->logAction($trx, 'Received', $this->recipientUser1, "Document received by {$this->recipientUser1->office_name}");
        $this->logAction($trx, 'Done', $this->recipientUser1, "Auto-completed (For Information) by {$this->recipientUser1->office_name}");
        $recipient->update(['isActive' => false]);

        // Evaluate status
        $status = TransactionStatusService::evaluate($trx->transaction_no);

        $this->info("  Transaction Status: {$status}");
        $this->info("  Document Status: {$doc->fresh()->status}");

        return ['transaction' => $trx, 'document' => $doc, 'status' => $status];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MULTIPLE ROUTING SCENARIOS (placeholder)
    // ─────────────────────────────────────────────────────────────────────────

    private function runMultipleScenarios(string $scenario): int
    {
        $this->info("=== MULTIPLE ROUTING SCENARIOS ===");
        $this->warn("Multiple scenarios not yet implemented. Run: php artisan mock:transaction multiple --list");
        return Command::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SEQUENTIAL ROUTING SCENARIOS (placeholder)
    // ─────────────────────────────────────────────────────────────────────────

    private function runSequentialScenarios(string $scenario): int
    {
        $this->info("=== SEQUENTIAL ROUTING SCENARIOS ===");
        $this->warn("Sequential scenarios not yet implemented. Run: php artisan mock:transaction sequential --list");
        return Command::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPER METHODS
    // ─────────────────────────────────────────────────────────────────────────

    private function runScenario(string $name, callable $callback): void
    {
        $this->newLine();
        $this->info("▶ Running: {$name}");
        
        try {
            DB::beginTransaction();
            $result = $callback();
            DB::commit();
            
            $this->info("✓ Completed: {$name}");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("✗ Failed: {$name} - {$e->getMessage()}");
        }
        
        $this->newLine();
        $this->line(str_repeat('-', 60));
    }

    private function createDocument(string $suffix, string $trxType = 'Default', string $actionType = 'Appropriate Action'): Document
    {
        $docNo = 'MOCK-' . date('Ymd-His') . '-' . $suffix;

        return Document::create([
            'document_no' => $docNo,
            'subject' => "Test Document - {$suffix}",
            'document_type' => 'Memorandum',
            'action_type' => $actionType,
            'origin_type' => 'Internal',
            'status' => 'Draft',
            'office_id' => $this->originUser->office_id,
            'office_name' => $this->originUser->office_name,
            'created_by_id' => $this->originUser->id,
            'created_by_name' => $this->originUser->fullName(),
            'allow_copy' => false,
        ]);
    }

    private function createTransaction(
        Document $doc, 
        string $routing, 
        string $actionType, 
        string $transactionType = 'Default',
        ?string $parentTrxNo = null
    ): DocumentTransaction {
        $trxNo = 'TRX-' . date('Ymd-His') . '-' . Str::random(6);

        return DocumentTransaction::create([
            'transaction_no' => $trxNo,
            'transaction_type' => $transactionType,
            'parent_transaction_no' => $parentTrxNo,
            'routing' => $routing,
            'document_no' => $doc->document_no,
            'document_type' => $doc->document_type,
            'action_type' => $actionType,
            'origin_type' => $doc->origin_type,
            'subject' => $doc->subject,
            'remarks' => 'Mock transaction for testing',
            'status' => 'Draft',
            'urgency_level' => 'High',
            'due_date' => now()->addDays(3),
            'office_id' => $this->originUser->office_id,
            'office_name' => $this->originUser->office_name,
            'created_by_id' => $this->originUser->id,
            'created_by_name' => $this->originUser->fullName(),
            'isActive' => true,
        ]);
    }

    private function addRecipient(DocumentTransaction $trx, User $recipientUser, string $type, int $sequence, ?User $createdBy = null): DocumentRecipient
    {
        // Creator is typically the origin user who released the document
        $creator = $createdBy ?? $this->originUser;
        
        return DocumentRecipient::create([
            'transaction_no' => $trx->transaction_no,
            'document_no' => $trx->document_no,
            'office_id' => $recipientUser->office_id,
            'office_name' => $recipientUser->office_name,
            'recipient_type' => $type,
            'sequence' => $sequence,
            'isActive' => true,
            'created_by_id' => $creator->id,
            'created_by_name' => $creator->fullName(),
        ]);
    }

    private function logAction(
        DocumentTransaction $trx, 
        string $status, 
        User $user, 
        string $activity,
        ?string $routedOfficeId = null,
        ?string $routedOfficeName = null,
        ?string $reason = null
    ): DocumentTransactionLog {
        return DocumentTransactionLog::create([
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
    }
}
