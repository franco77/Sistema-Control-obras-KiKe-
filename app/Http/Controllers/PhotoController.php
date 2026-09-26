<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PortalAccessToken;
use App\Models\Project;
use App\Models\ProjectPhoto;
use App\Services\Documents\PhotoService;
use Illuminate\Http\Request;

/** Sirve las fotos de obra respetando visible_to_client. */
class PhotoController extends Controller
{
    public function __construct(private readonly PhotoService $photos) {}

    public function show(Request $request, ProjectPhoto $photo)
    {
        if ($request->user()) {
            $this->authorize('view', $photo->project);
        } else {
            /** @var PortalAccessToken|null $token */
            $token = $request->attributes->get('portalToken');

            abort_if($token === null, 403);
            abort_unless($photo->visible_to_client, 403);

            $target = $token->tokenable;
            $projectId = $target instanceof Project ? $target->id : data_get($target, 'project_id');

            abort_unless($projectId === $photo->project_id, 403);
        }

        return $this->photos->response($photo, $request->boolean('thumb'));
    }
}