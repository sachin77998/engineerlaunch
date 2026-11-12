<?php

namespace App\Contracts;

use App\Models\Property\Property;
use Illuminate\Http\Request;

// An extra step of the post-property wizard, registered in config/property_steps.php. The wizard places it after the
// step named by after(), shows view() with viewData(), and calls save() on submit — no wizard code changes.
interface PropertyStep
{
    public function key(): string;

    public function label(): string;

    public function icon(): string;

    /** The built-in (or other extra) step this one follows. */
    public function after(): string;

    public function applies(Property $p): bool;

    public function view(): string;

    public function viewData(Property $p): array;

    /** Validates and stores the step. Throws a ValidationException on bad input. */
    public function save(Request $request, Property $p): void;
}
