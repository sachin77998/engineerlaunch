<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Property legal services catalogue, bookings (cart checkout) and the "maximum loan amount" note on home loan lenders.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_loan_banks', function (Blueprint $table) {
            $table->string('max_amount', 60)->nullable()->after('max_funding');
        });

        Schema::create('legal_services', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 120);
            $table->string('category', 20);
            $table->json('audiences');
            $table->string('summary', 300);
            $table->json('includes')->nullable();
            $table->json('documents')->nullable();
            $table->json('cities')->nullable(); // null = every city
            $table->unsignedInteger('price')->nullable(); // null = quoted after a call
            $table->string('price_note', 120)->nullable();
            $table->string('turnaround', 60)->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'category']);
        });

        Schema::create('legal_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('phone', 20);
            $table->string('email', 191)->nullable();
            $table->string('city', 80);
            $table->string('address', 255)->nullable();
            $table->dateTime('preferred_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('total')->default(0);
            $table->boolean('has_quote_items')->default(false);
            $table->string('status', 20)->default('requested');
            $table->text('admin_note')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('legal_booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('legal_service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->unsignedInteger('price')->nullable();
        });

        Schema::create('legal_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('focus', 300);
            $table->string('affiliation', 80)->nullable();
            $table->decimal('rating', 2, 1)->nullable();
            $table->unsignedInteger('cases_resolved')->nullable();
            $table->unsignedInteger('documents_reviewed')->nullable();
            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->json('cities')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('legal_booking_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('title', 120);
            $table->text('body');
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->index(['is_visible', 'created_at']);
        });

        $s = fn ($cat, $slug, $name, $aud, $summary, $includes, $docs, $cities = null) => compact('cat', 'slug', 'name', 'aud', 'summary', 'includes', 'docs', 'cities');
        $rows = [
            $s('agreements', 'memorandum-of-understanding', 'Memorandum of understanding', ['buyer', 'seller'],
                'Puts the terms both sides have agreed in principle on paper before the full contract is drafted.',
                ['Drafting by a property lawyer', 'Price, token amount and timelines', 'One round of changes'], ['ID proof of both parties', 'Property details']),
            $s('agreements', 'tripartite-agreement', 'Tripartite agreement', ['buyer'],
                'Three-party agreement between buyer, builder or seller and the lending bank when the loan is paid out directly.',
                ['Drafting in the format lenders accept', 'Coordination with the builder', 'Review of bank clauses'], ['Sanction letter', 'Allotment letter / agreement to sell', 'ID proof']),
            $s('agreements', 'agreement-to-sell', 'Agreement to sell', ['buyer', 'seller'],
                'Fixes price, payment schedule, possession and penalties between buyer and seller ahead of the sale deed.',
                ['Drafting and review', 'Payment schedule and default clauses', 'Stamp duty guidance'], ['Title documents', 'ID proof of both parties', 'Payment plan']),
            $s('agreements', 'rent-agreement', 'Rent / lease agreement', ['owner', 'general'],
                'Rental contract that protects landlord and tenant — rent, deposit, lock-in, maintenance and notice.',
                ['Drafting on stamp paper or e-stamp', 'Clauses for deposit, lock-in and notice', 'Doorstep or online signing'], ['Owner and tenant ID proof', 'Property address', 'Rent and deposit amounts']),
            $s('agreements', 'agreement-review', 'Agreement review by a lawyer', ['buyer', 'seller', 'owner'],
                'A lawyer reads the draft you received and marks risky or missing clauses before you sign.',
                ['Clause-by-clause review', 'Written comments', 'Call to explain the changes'], ['Draft agreement']),

            $s('verification', 'legal-title-opinion', 'Legal title opinion', ['buyer'],
                'Checks the chain of ownership, encumbrances and litigation, and gives a written opinion on whether the title is clear.',
                ['Title search for 30 years', 'Encumbrance and litigation check', 'Written title report'], ['Sale deeds / chain documents', 'Latest tax receipt', 'Khata / mutation copy']),
            $s('verification', 'encumbrance-certificate', 'Encumbrance certificate', ['buyer', 'seller'],
                'Gets the certificate from the Sub-Registrar showing mortgages or registered transactions on the property.',
                ['Application at the Sub-Registrar', 'Follow-up until issued', 'Soft and certified copy'], ['Property address and survey number', 'Previous deed copy']),
            $s('verification', 'property-valuation', 'Property valuation report', ['buyer', 'seller', 'owner'],
                'An approved valuer inspects the property and issues a valuation report for negotiation, loans or tax.',
                ['Site inspection', 'Comparable sales check', 'Signed valuation report'], ['Property documents', 'Approved plan if any']),
            $s('verification', 'project-due-diligence', 'Builder project check (RERA & approvals)', ['buyer'],
                'Verifies the project\'s RERA registration, land title, building approvals and the builder\'s pending cases.',
                ['RERA and approvals check', 'Land title summary', 'Builder litigation search'], ['Project name and RERA number', 'Brochure / allotment letter']),

            $s('deeds', 'sale-deed', 'Sale deed', ['buyer', 'seller'],
                'Drafts the deed that transfers ownership from seller to buyer, ready for registration.',
                ['Drafting and review', 'Stamp duty calculation', 'Registration support'], ['Agreement to sell', 'Title documents', 'ID and PAN of both parties']),
            $s('deeds', 'gift-deed', 'Gift deed', ['owner', 'general'],
                'Transfers property to a family member without payment, with the stamp duty concessions that apply.',
                ['Drafting', 'Stamp duty check for family transfers', 'Registration support'], ['Title documents', 'ID proof of donor and receiver', 'Relationship proof']),
            $s('deeds', 'partition-deed', 'Partition deed', ['owner', 'general'],
                'Divides a jointly owned property among co-owners so each gets a clearly marked share.',
                ['Drafting with share schedule', 'Site sketch reference', 'Registration support'], ['Title documents', 'ID proof of all co-owners', 'Proposed division']),
            $s('deeds', 'release-deed', 'Release deed', ['owner', 'general'],
                'Records a co-owner giving up their share in favour of the others, usually within the family.',
                ['Drafting', 'Stamp duty check', 'Registration support'], ['Title documents', 'ID proof of all parties']),
            $s('deeds', 'power-of-attorney', 'Power of attorney', ['seller', 'owner', 'general'],
                'Authorises someone to sign, register or manage the property for you — general or for a specific deal.',
                ['Drafting (general or special)', 'Notarisation or registration', 'Embassy attestation guidance for NRIs'], ['ID proof of both parties', 'Property details']),
            $s('deeds', 'will-drafting', 'Will drafting', ['owner', 'general'],
                'Drafts a will covering your property and assets so it passes to the people you choose.',
                ['Consultation and drafting', 'Witness arrangement', 'Optional registration'], ['ID proof', 'List of assets and beneficiaries']),

            $s('registration', 'registration', 'Registration at Sub-Registrar', ['buyer', 'seller', 'owner'],
                'Handles the appointment, stamp duty payment and filing of an already drafted deed at the Sub-Registrar.',
                ['Appointment booking', 'Stamp duty and fee payment help', 'Accompanied visit'], ['Final deed', 'ID and PAN of parties', 'Two witnesses']),
            $s('registration', 'notarisation', 'Notarisation', ['general', 'owner'],
                'Arranges a notary to attest affidavits, agreements and declarations.',
                ['Notary appointment', 'Attestation', 'Doorstep option where available'], ['Document to notarise', 'ID proof']),
            $s('registration', 'e-stamping', 'E-stamping', ['buyer', 'seller', 'owner', 'general'],
                'Works out the stamp duty for your document and buys the e-stamp paper online.',
                ['Stamp duty calculation', 'E-stamp purchase', 'Delivery of stamp paper'], ['Document type and value', 'Party names']),
            $s('registration', 'affidavit', 'Affidavit drafting', ['general'],
                'Drafts affidavits for name change, address, ownership or loss of documents.',
                ['Drafting', 'Stamp paper', 'Notarisation'], ['ID proof', 'Details to declare']),

            $s('ca', 'tds-assistance', 'TDS on property purchase', ['buyer'],
                'Checks whether 1% TDS applies to your purchase and calculates the amount to deduct.',
                ['Applicability check', 'TDS calculation', 'Payment help'], ['Agreement value', 'Seller PAN']),
            $s('ca', 'form-26qb', 'TDS return (Form 26QB) and Form 16B', ['buyer'],
                'Files Form 26QB for the TDS deducted on the purchase and downloads Form 16B for the seller.',
                ['Form 26QB filing', 'Challan payment help', 'Form 16B download'], ['Buyer and seller PAN', 'Payment details']),
            $s('ca', 'tan-assistance', 'TAN application', ['buyer', 'general'],
                'Applies for a Tax Deduction Account Number when one is needed, such as for NRI sellers.',
                ['Application filing', 'Follow-up until allotted'], ['PAN', 'Address proof']),
            $s('ca', 'capital-gains', 'Capital gains tax planning', ['seller'],
                'A CA works out the capital gains on your sale and the exemptions you can use.',
                ['Gain calculation with indexation where allowed', 'Exemption options', 'Summary report'], ['Purchase and sale deeds', 'Improvement cost proofs']),

            $s('khata', 'khata-transfer', 'Khata / mutation transfer', ['buyer', 'owner'],
                'Gets the municipal or revenue record changed into your name after a purchase, gift or inheritance.',
                ['Application filing', 'Follow-up with the office', 'Updated record copy'], ['Registered deed', 'Latest tax receipt', 'ID proof']),
            $s('khata', 'b-to-a-khata', 'B-Khata to A-Khata conversion', ['owner'],
                'Moves a B-Khata property to A-Khata so it can get loans, building plan approval and full records.',
                ['Eligibility check', 'Application filing', 'Follow-up until issued'], ['B-Khata extract', 'Tax receipts', 'Title documents'], ['Bengaluru']),
            $s('khata', 'property-tax-name-change', 'Property tax name change', ['buyer', 'owner'],
                'Updates the owner\'s name on the municipal property tax account.',
                ['Application', 'Follow-up', 'Updated tax receipt'], ['Registered deed', 'Last tax receipt']),

            $s('consult', 'lawyer-consultation', 'Talk to a property lawyer', ['buyer', 'seller', 'owner', 'general'],
                'A 30-minute call with a property lawyer for any question — documents, disputes, tenants or inheritance.',
                ['30-minute call', 'Follow-up summary by email'], []),
        ];
        $now = now();
        DB::table('legal_services')->insert(array_map(fn ($r, $i) => [
            'slug' => $r['slug'], 'name' => $r['name'], 'category' => $r['cat'], 'audiences' => json_encode($r['aud']), 'summary' => $r['summary'],
            'includes' => json_encode($r['includes']), 'documents' => json_encode($r['docs']), 'cities' => $r['cities'] ? json_encode($r['cities']) : null,
            'sort' => $i, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ], $rows, array_keys($rows)));
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_booking_reviews');
        Schema::dropIfExists('legal_partners');
        Schema::dropIfExists('legal_booking_items');
        Schema::dropIfExists('legal_bookings');
        Schema::dropIfExists('legal_services');
        Schema::table('home_loan_banks', fn (Blueprint $table) => $table->dropColumn('max_amount'));
    }
};
