<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CompanyCategorySeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'collection' => ['Global Companies'=>'🌐','Indian Enterprises'=>'IN','Startups'=>'🚀','Financial Technology'=>'₹','Healthcare Innovators'=>'✚','Education Technology'=>'🎓','Internet Businesses'=>'⌁'],
            'company_type' => ['Foreign MNC'=>'FM','Indian MNC'=>'IM','Corporate'=>'CO','Startup'=>'SU','Government / PSU'=>'GV','Non-profit'=>'NP'],
            'industry' => [
                'IT Services'=>['IT Services & Consulting','Hardware & Networking'],
                'Technology'=>['Software Products','Electronic Components & Semiconductors','Electronics Manufacturing','Internet','Emerging Technologies'],
                'Manufacturing & Production'=>['Industrial Equipment & Machinery','Auto Components','Chemicals','Automobile','Building Materials','Industrial Automation','Electrical Equipment','Iron & Steel','Packaging & Containers','Metals & Mining','Defence & Aerospace','Petrochemicals & Polymers','Agricultural Chemicals','Pulp & Paper'],
                'Healthcare & Life Sciences'=>['Pharmaceuticals & Life Sciences','Medical Services & Hospitals','Medical Devices & Equipment','Clinical Research','Biotechnology'],
                'Infrastructure, Transport & Real Estate'=>['Engineering & Construction','Courier & Logistics','Power','Oil & Gas','Real Estate','Environmental Services','Aviation','Ports & Shipping','Urban Transport'],
                'Banking & Financial Services'=>['Financial Services','Financial Technology & Payments','Banking','Insurance','Non-banking Finance','Investment & Private Capital'],
                'Professional Services'=>['Recruitment & Staffing','Management Consulting','Accounting & Auditing','Facilities Management','Security Services','Content & Language Services','Design','Testing & Certification','Legal Services','Architecture & Interior Design'],
                'Business Process Management'=>['Analytics & Research Services','BPM & BPO'],
                'Consumer, Retail & Hospitality'=>['Consumer Electronics & Appliances','Textiles & Apparel','Food Processing','Hotels & Restaurants','Retail','Travel & Tourism','FMCG','Beverages','Beauty & Personal Care','Furniture & Furnishings','Gems & Jewellery','Fitness & Wellness','Leather & Footwear'],
                'Media, Entertainment & Telecom'=>['Telecom & ISP','Advertising & Marketing','Printing & Publishing','Gaming','Film, Music & Entertainment','Animation & VFX','Events','Sports & Recreation','TV & Radio'],
                'Education'=>['E-learning & Education Technology','Education & Training'],
                'Other Industries'=>['Social Services & Associations','Agriculture, Forestry & Fishing','Import, Export & Wholesale'],
            ],
            'department' => ['Software Engineering & QA'=>'SW','Sales & Business Development'=>'SA','Manufacturing & Engineering'=>'ME','Finance & Accounting'=>'FA','Customer Success & Operations'=>'CS','Data Science & Analytics'=>'DS','IT & Information Security'=>'IS','Human Resources'=>'HR','Project & Program Management'=>'PM','Hardware & Networks'=>'HW','Consulting'=>'CO','Procurement & Supply Chain'=>'PS','Marketing & Communications'=>'MK','Quality Assurance'=>'QA','Research & Development'=>'RD','UX, Design & Architecture'=>'UX','Product Management'=>'PR','Legal & Regulatory'=>'LR'],
        ];

        foreach ($groups as $taxonomy => $items) {
            $order = 0;
            foreach ($items as $name => $value) {
                if (is_array($value)) {
                    $parent = $this->category($taxonomy, $name, null, '▦', $order++);
                    foreach ($value as $child) $this->category($taxonomy, $child, $parent->id, '•', $order++);
                } else {
                    $this->category($taxonomy, is_int($name) ? $value : $name, null, is_int($name) ? '•' : $value, $order++);
                }
            }
        }

        Company::query()->select(['id','name','country','industry','sector','company_type','organization_type','business_type'])->chunkById(200, function ($companies) {
            foreach ($companies as $company) $this->classify($company);
        });
    }

    private function category(string $taxonomy, string $name, ?int $parent, string $symbol, int $order): CompanyCategory
    {
        return CompanyCategory::updateOrCreate(['slug'=>Str::slug($taxonomy.'-'.$name)], ['parent_id'=>$parent,'name'=>$name,'taxonomy'=>$taxonomy,'symbol'=>$symbol,'sort_order'=>$order,'is_active'=>true]);
    }

    private function classify(Company $company): void
    {
        $text = Str::lower(implode(' ', array_filter([$company->industry,$company->sector,$company->company_type,$company->organization_type,$company->business_type])));
        $matches = [];
        $map = [
            'software'=>'Software Products','it service'=>'IT Services & Consulting','consult'=>'Management Consulting','semiconductor'=>'Electronic Components & Semiconductors','hardware'=>'Hardware & Networking','internet'=>'Internet','fintech'=>'Financial Technology & Payments','bank'=>'Banking','financial'=>'Financial Services','insurance'=>'Insurance','health'=>'Medical Services & Hospitals','pharma'=>'Pharmaceuticals & Life Sciences','education'=>'E-learning & Education Technology','retail'=>'Retail','telecom'=>'Telecom & ISP','manufactur'=>'Industrial Equipment & Machinery','automobile'=>'Automobile','logistic'=>'Courier & Logistics','recruit'=>'Recruitment & Staffing','media'=>'Film, Music & Entertainment','security'=>'IT & Information Security','data'=>'Data Science & Analytics',
        ];
        foreach ($map as $needle => $category) if (Str::contains($text, $needle)) $matches[] = $category;
        if (Str::contains($text, 'startup')) $matches[] = 'Startups';
        if (Str::contains($text, ['mnc','multinational']) || ($company->country && Str::lower($company->country) !== 'india')) $matches[] = 'Global Companies';
        if (Str::lower((string)$company->country) === 'india') $matches[] = 'Indian Enterprises';
        $ids = CompanyCategory::whereIn('name', array_unique($matches))->pluck('id');
        if ($ids->isNotEmpty()) $company->categories()->syncWithoutDetaching($ids);
    }
}
