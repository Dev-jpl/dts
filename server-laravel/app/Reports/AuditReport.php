<?php

namespace App\Reports;

use App\Models\Document;
use Illuminate\Support\Facades\DB;

/**
 * Transaction Audit Report — per document.
 *
 * Produces a full integrity log: all versions, all logs, all parties,
 * all attachments, all notes.
 *
 * Export: PDF ONLY — never Excel (rule #29).
 */
class AuditReport
{
    public static function getData(string $docNo): ?array
    {
        // ── Document ──────────────────────────────────────────────────────────
        $document = DB::table('documents')->where('document_no', $docNo)->first();
        if (!$document) {
            return null;
        }

        // ── Transactions ──────────────────────────────────────────────────────
        $transactions = DB::table('document_transactions')
            ->where('document_no', $docNo)
            ->orderBy('created_at')
            ->get();

        // ── All transaction logs ──────────────────────────────────────────────
        $allTrxNos = $transactions->pluck('transaction_no')->toArray();

        $logs = DB::table('document_transaction_logs')
            ->whereIn('transaction_no', $allTrxNos)
            ->orderBy('created_at')
            ->get()
            ->map(fn($l) => (array) $l)
            ->toArray();

        // ── Recipients (all, including isActive=false) ─────────────────────────
        $recipients = DB::table('document_recipients')
            ->whereIn('transaction_no', $allTrxNos)
            ->orderBy('sequence')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        // ── Document versions ─────────────────────────────────────────────────
        $versions = DB::table('document_versions')
            ->where('document_no', $docNo)
            ->orderBy('version_number')
            ->get()
            ->map(fn($v) => (array) $v)
            ->toArray();

        // ── Official notes ────────────────────────────────────────────────────
        $notes = DB::table('document_notes')
            ->where('document_no', $docNo)
            ->orderBy('created_at')
            ->get()
            ->map(fn($n) => (array) $n)
            ->toArray();

        // ── Attachments ───────────────────────────────────────────────────────
        $attachments = DB::table('document_attachments')
            ->whereIn('transaction_no', $allTrxNos)
            ->orderBy('created_at')
            ->get()
            ->map(fn($a) => (array) $a)
            ->toArray();

        return [
            'document'     => (array) $document,
            'transactions' => $transactions->map(fn($t) => (array) $t)->toArray(),
            'logs'         => $logs,
            'recipients'   => $recipients,
            'versions'     => $versions,
            'notes'        => $notes,
            'attachments'  => $attachments,
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
