<?php
return [
    'ca_bundle' => env('JOB_SCRAPER_CA_BUNDLE', PHP_OS_FAMILY === 'Windows' && is_file(storage_path('app/private/industrial-ca.pem')) ? storage_path('app/private/industrial-ca.pem') : true),
    'feeds' => [
        ['name'=>'Happy Forgings','website'=>'https://happyforgingsltd.com','careers_url'=>'https://happyforgingsltd.com/opportunities/','ats_provider'=>'happy_forgings','industry'=>'Forging and Machining'],
        ['name'=>'Mahindra and Mahindra','website'=>'https://www.mahindra.com','careers_url'=>'https://jobs.mahindracareers.com/','jobs_feed_url'=>'https://jobs.mahindracareers.com/search/?q=&sortColumn=referencedate&sortDirection=desc','ats_provider'=>'successfactors','industry'=>'Automobile and Tractor Manufacturing'],
        ['name'=>'Sonalika','website'=>'https://www.sonalika.com','careers_url'=>'https://www.sonalika.com/careers.html','ats_provider'=>'sonalika','industry'=>'Tractor Manufacturing'],
        ['name'=>'Tata Motors','website'=>'https://www.tatamotors.com','careers_url'=>'https://careers.tatamotors.com/','jobs_feed_url'=>'https://careers.tatamotors.com/search/?q=&sortColumn=referencedate&sortDirection=desc','ats_provider'=>'successfactors','industry'=>'Automobile'],
        ['name'=>'Hero MotoCorp','website'=>'https://www.heromotocorp.com','careers_url'=>'https://jobs.heromotocorp.com/','jobs_feed_url'=>'https://jobs.heromotocorp.com/search/?q=&sortColumn=referencedate&sortDirection=desc','ats_provider'=>'successfactors','industry'=>'Automobile'],
    ],
    // Research sources are evidence of establishments or career channels,
    // not an assertion of vacancies, exact estate membership or crawler support.
    'research' => [
        ['name'=>'Nestle India','region'=>'Multiple factories including Pantnagar','url'=>'https://www.nestle.in/jobs','kind'=>'Company careers','status'=>'Career channel identified; vacancy parser pending'],
        ['name'=>'Britannia Industries','region'=>'Multiple Indian factories','url'=>'https://www.britannia.co.in/careers','kind'=>'Company careers','status'=>'Career channel identified; vacancy parser pending'],
        ['name'=>'Ashok Leyland','region'=>'Multiple Indian factories','url'=>'https://ashokleyland.com/in/careers','kind'=>'Company careers','status'=>'Career channel identified; vacancy parser pending'],
        ['name'=>'SIIDCUL','region'=>'Uttarakhand: Pantnagar, Rudrapur, Haridwar, Kashipur, Sitarganj','url'=>'https://siidcul.com/','kind'=>'Official industrial-estate directory','status'=>'Estate research; allottee records require source verification'],
        ['name'=>'PSIEC','region'=>'Punjab industrial focal points','url'=>'https://psiecems.org/','kind'=>'Official estate management portal','status'=>'Estate research; some allottee services require login'],
        ['name'=>'Happy Forgings','region'=>'Ludhiana','url'=>'https://happyforgingsltd.com/opportunities/','kind'=>'Company careers','status'=>'Official vacancy importer configured; listing location is not specified'],
        ['name'=>'Euro Cast & Forge','region'=>'Ludhiana','url'=>'https://www.eurocastforge.com/','kind'=>'Company website','status'=>'Manufacturer identified; no vacancy feed verified'],
        ['name'=>'GNA Axles','region'=>'Mehtiana, Hoshiarpur district','url'=>'https://www.gnaaxles.in/','kind'=>'Company website','status'=>'Manufacturer identified; no vacancy feed verified'],
        ['name'=>'Vardhman Textiles','region'=>'Multiple manufacturing locations','url'=>'https://www.vardhman.com/Careers/LifeAtVardhman','kind'=>'Company careers','status'=>'Career channel identified; vacancy parser pending'],
        ['name'=>'Hawkins Cookers','region'=>'Multiple factories including Hoshiarpur','url'=>'https://www.hawkinscookers.com/Images/SenMgmt.pdf','kind'=>'Historical recruitment document (2023)','status'=>'Historical evidence only; do not import as current vacancies'],
    ],
];
