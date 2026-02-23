<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Allow all authenticated users, or add role checks here
        return true;
    }

    public function rules(): array
    {
        return [
            'documentType.id'      => 'required|string',
            'documentType.code'    => 'required|string',
            'documentType.type'    => 'required|string',
            'actionTaken.id'       => 'required|integer',
            'actionTaken.action'   => 'required|string',
            'originType'           => 'required|in:Internal,External,Email',
            'subject'              => 'required|string',
            'remarks'              => 'nullable|string',
            'sender'               => 'nullable|string|max:255',
            'sender_position'      => 'nullable|string|max:255',
            'sender_office'        => 'nullable|string|max:255',
            'sender_email'         => 'nullable|email',
            'routing'             => 'required|string',
            'recipients'           => 'required|array|min:1',
            'recipients.*.office'  => 'required|string',
            'recipients.*.recipient_type'  => 'nullable|string',
            'recipients.*.office_code' => 'required|string',
            'recipients.*.sequence' => 'nullable|integer',
            'signatories'          => 'array',
            // 'signatories.*.name'   => 'required_with:signatories|string',
            // 'signatories.*.position' => 'required_with:signatories|string',
            'files'                => 'array',
            'attachments'          => 'array',
            'isDone'               => 'boolean',
            'isBindDocument'       => 'boolean',
            'bindedDocuments'      => 'array',
        ];
    }
}
