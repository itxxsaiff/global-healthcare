<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PractitionerDocument;
use App\Services\Media\DocumentStore;
use Illuminate\Http\Request;

/** The only way to view a verification document: owner or admin, every admin view audit-logged. */
class DocumentController extends Controller
{
    public function show(Request $request, PractitionerDocument $document, DocumentStore $store)
    {
        $user = $request->user();
        $isOwner = $document->practitioner->user_id === $user->id;

        abort_unless($isOwner || $user->isAdmin(), 403);

        if ($user->isAdmin()) {
            AuditLog::record('document.viewed', $document, ['type' => $document->type->value, 'practitioner_id' => $document->practitioner_profile_id]);
        }

        return $store->response($document);
    }
}
