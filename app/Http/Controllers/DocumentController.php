<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\PortalAccessToken;
use App\Services\Documents\DocumentService;
use Illuminate\Http\Request;

/**
 * Descarga controlada de documentos. Nunca se exponen URLs públicas:
 * cada descarga valida o bien la sesión del panel, o bien el token de portal
 * y que el documento sea visible para el cliente.
 */
class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documents) {}

    public function download(Request $request, Document $document)
    {
        $this->authorizeAccess($request, $document);

        return $this->documents->download($document);
    }

    private function authorizeAccess(Request $request, Document $document): void
    {
        if ($request->user()) {
            $this->authorize('view', $document);

            return;
        }

        /** @var PortalAccessToken|null $token */
        $token = $request->attributes->get('portalToken');

        abort_if($token === null, 403);
        abort_unless($document->visible_to_client, 403);
        abort_unless($this->belongsToToken($document, $token), 403);
    }

    /** El documento debe pertenecer al recurso del token o a su obra/cliente. */
    private function belongsToToken(Document $document, PortalAccessToken $token): bool
    {
        $owner = $document->documentable;
        $target = $token->tokenable;

        if ($owner === null || $target === null) {
            return false;
        }

        if ($owner->is($target)) {
            return true;
        }

        $projectId = data_get($target, 'project_id') ?? ($target instanceof \App\Models\Project ? $target->id : null);

        return $projectId !== null
            && ($owner instanceof \App\Models\Project ? $owner->id : data_get($owner, 'project_id')) === $projectId;
    }
}