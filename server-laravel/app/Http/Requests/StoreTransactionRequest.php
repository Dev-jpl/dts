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
            // document info - required
            'documentType.id'      => 'required|string',
            'documentType.code'    => 'required|string',
            'documentType.type'    => 'required|string',
            'actionTaken.id'       => 'required|integer',
            'actionTaken.action'   => 'required|string',
            'originType'           => 'required|in:Internal,External,Email',
            'subject'              => 'required|string',
            'remarks'              => 'nullable|string',

            // Optional sender info for external documents
            'sender'               => 'nullable|string|max:255',
            'sender_position'      => 'nullable|string|max:255',
            'sender_office'        => 'nullable|string|max:255',
            'sender_email'         => 'nullable|email',

            // routing informations and recipients - required
            'routing'             => 'required|string',
            'recipients'           => 'required|array|min:1',
            'recipients.*.office'  => 'required|string',
            'recipients.*.recipient_type'  => 'nullable|string',
            'recipients.*.office_code' => 'required|string',
            'recipients.*.sequence' => 'nullable|integer',

            // signatories - optional
            'signatories'          => 'array',
            // 'signatories.*.name'   => 'required_with:signatories|string',
            // 'signatories.*.position' => 'required_with:signatories|string',

            //files
            'files'                   => 'nullable|array',
            'files.*.name'            => 'required_with:files|string',
            'files.*.temp_path'       => 'required_with:files|string',
            'files.*.type'            => 'required_with:files|string',
            'files.*.size_bytes'      => 'required_with:files|integer',

            'attachments'                  => 'nullable|array',
            'attachments.*.name'           => 'required_with:attachments|string',
            'attachments.*.temp_path'      => 'required_with:attachments|string',
            'attachments.*.type'           => 'required_with:attachments|string',
            'attachments.*.size_bytes'     => 'required_with:attachments|integer',

            // Optional fields for binding documents
            'isDone'               => 'boolean',
            'isBindDocument'       => 'boolean',
            'bindedDocuments'      => 'array',
        ];
    }
}
