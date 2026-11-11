<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Home services: the catalogue (owner sets prices), bookings with a date / time slot, and booked items.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_services', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 120);
            $table->string('category', 20);
            $table->string('summary', 300);
            $table->json('includes')->nullable();
            $table->unsignedInteger('price')->nullable(); // null = price after inspection
            $table->string('unit', 30)->default('per visit');
            $table->json('cities')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'category']);
        });

        Schema::create('home_service_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 20);
            $table->string('city', 80);
            $table->string('name', 120);
            $table->string('phone', 15);
            $table->string('address', 300);
            $table->string('pincode', 6)->nullable();
            $table->date('slot_date');
            $table->string('slot', 5);
            $table->json('details')->nullable(); // packers & movers: from / to / size / extras
            $table->unsignedInteger('total')->default(0);
            $table->boolean('has_quote_items')->default(false);
            $table->string('notes', 500)->nullable();
            $table->string('status', 12)->default('requested');
            $table->string('admin_note', 500)->nullable();
            $table->timestamps();
            $table->index(['status', 'slot_date']);
        });

        Schema::create('home_service_booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_service_booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->unsignedSmallInteger('qty')->default(1);
            $table->unsignedInteger('price')->nullable();
            $table->string('unit', 30)->nullable();
        });

        $now = now();
        $rows = [
            ['cleaning', 'full-home-deep-cleaning', 'Full home deep cleaning', 'Floors, kitchen, bathrooms, windows, fans and fixtures cleaned top to bottom with machines.', ['Kitchen & bathroom degreasing', 'Floor scrubbing', 'Fans, lights, windows'], 'per BHK'],
            ['cleaning', 'kitchen-deep-cleaning', 'Kitchen deep cleaning', 'Cabinets inside and out, chimney exterior, tiles, slab and sink degreased.', ['Cabinets in & out', 'Tiles and slab', 'Sink and taps'], 'per kitchen'],
            ['cleaning', 'bathroom-deep-cleaning', 'Bathroom deep cleaning', 'Tiles, fittings, toilet and mirrors descaled and disinfected.', ['Hard-water stain removal', 'Disinfection'], 'per bathroom'],
            ['cleaning', 'sofa-cleaning', 'Sofa shampooing', 'Fabric sofa shampooed and vacuum-dried.', ['Shampoo & vacuum', 'Stain treatment'], 'per seat'],
            ['cleaning', 'water-tank-cleaning', 'Water tank cleaning', 'Overhead or underground tank emptied, scrubbed and disinfected.', ['Sludge removal', 'Anti-bacterial wash'], 'per tank'],
            ['cleaning', 'move-in-cleaning', 'Move-in / move-out cleaning', 'Empty-home cleaning before you move in or hand back the keys.', ['All rooms, kitchen, bathrooms', 'Cupboards inside'], 'per BHK'],
            ['painting', 'full-home-painting', 'Full home painting', 'Interior painting of the whole home with putty touch-up and masking.', ['Free colour consultation', 'Masking & cleanup'], 'per home'],
            ['painting', 'room-painting', 'Single room / wall painting', 'Fresh coat for one room or an accent wall.', ['Putty touch-up', 'Two coats'], 'per room'],
            ['painting', 'waterproofing', 'Waterproofing', 'Terrace, bathroom or wall dampness treatment after inspection.', ['Site inspection', 'Treatment plan'], 'per job'],
            ['painting', 'wood-polish', 'Wood polish', 'Doors and furniture sanded and polished.', ['Sanding', 'Polish coats'], 'per item'],
            ['repairs', 'electrician-visit', 'Electrician visit', 'Wiring faults, switches, sockets, MCB trips and fittings.', ['Inspection', 'Minor repairs'], 'per visit'],
            ['repairs', 'fan-installation', 'Fan installation', 'Ceiling or wall fan fitted or replaced.', ['Fitting', 'Testing'], 'per fan'],
            ['repairs', 'plumber-visit', 'Plumber visit', 'Leaks, blockages, taps, flush tanks and pipe fittings.', ['Inspection', 'Minor repairs'], 'per visit'],
            ['repairs', 'tap-repair', 'Tap / leak repair', 'Dripping taps and small leaks fixed.', ['Washer or cartridge change'], 'per tap'],
            ['repairs', 'carpenter-visit', 'Carpenter visit', 'Doors, hinges, drawers, locks and furniture repairs.', ['Inspection', 'Minor repairs'], 'per visit'],
            ['repairs', 'furniture-assembly', 'Furniture assembly', 'Beds, wardrobes, tables and shelves assembled.', ['Assembly', 'Wall mounting where needed'], 'per item'],
            ['appliances', 'ac-service', 'AC service', 'Jet cleaning of filters, coils and drain for split or window AC.', ['Filter & coil cleaning', 'Gas pressure check'], 'per AC'],
            ['appliances', 'ac-gas-refill', 'AC gas refill', 'Leak check and gas top-up.', ['Leak test', 'Gas refill'], 'per AC'],
            ['appliances', 'ac-install', 'AC installation / uninstallation', 'Split or window AC fitted or removed.', ['Mounting', 'Testing'], 'per AC'],
            ['appliances', 'ro-service', 'Water purifier (RO) service', 'Filter change and servicing for RO / UV purifiers.', ['Filter check', 'Servicing'], 'per unit'],
            ['appliances', 'washing-machine-repair', 'Washing machine repair', 'Diagnosis and repair; spare parts charged extra.', ['Diagnosis', 'Repair'], 'per visit'],
            ['appliances', 'fridge-repair', 'Refrigerator repair', 'Cooling and electrical faults diagnosed and repaired.', ['Diagnosis', 'Repair'], 'per visit'],
            ['appliances', 'geyser-repair', 'Geyser repair / installation', 'Geyser fitted, repaired or descaled.', ['Fitting or repair'], 'per unit'],
            ['appliances', 'chimney-cleaning', 'Kitchen chimney cleaning', 'Filters and hood degreased.', ['Filter degreasing', 'Hood cleaning'], 'per unit'],
            ['pest', 'general-pest-control', 'General pest control', 'Cockroaches, ants and spiders treated with odourless gel and spray.', ['Gel + spray', 'Safe for children & pets after drying'], 'per BHK'],
            ['pest', 'termite-treatment', 'Termite treatment', 'Drill-fill-seal treatment for termites after inspection.', ['Inspection', 'Drill-fill-seal'], 'per home'],
            ['pest', 'bed-bug-treatment', 'Bed bug treatment', 'Two-visit treatment for mattresses, furniture and cracks.', ['Two visits'], 'per BHK'],
            ['pest', 'mosquito-control', 'Mosquito control', 'Indoor spray and outdoor fogging around the home.', ['Spray + fogging'], 'per home'],
        ];
        DB::table('home_services')->insert(array_map(fn ($r, $i) => ['category' => $r[0], 'slug' => $r[1], 'name' => $r[2], 'summary' => $r[3], 'includes' => json_encode($r[4]), 'unit' => $r[5],
            'sort' => $i, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now], $rows, array_keys($rows)));
    }

    public function down(): void
    {
        Schema::dropIfExists('home_service_booking_items');
        Schema::dropIfExists('home_service_bookings');
        Schema::dropIfExists('home_services');
    }
};
