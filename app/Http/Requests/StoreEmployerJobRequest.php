<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreEmployerJobRequest extends FormRequest
{
 public function authorize():bool{return in_array($this->user()?->role,['employer','admin'],true);}
 protected function prepareForValidation():void{foreach(['relocation_allowed','resume_required','cover_letter_required','portfolio_required','github_required','linkedin_required','is_confidential'] as $f)$this->merge([$f=>$this->boolean($f)]);}
 public function rules():array{$req=$this->input('action')==='publish'?'required':'nullable';return [
  'title'=>[$req,'string','max:255'],'category'=>[$req,'string','max:100'],'role'=>[$req,'string','max:120'],
  'job_type'=>[$req,Rule::in(['Full Time','Part Time','Contract','Freelance','Internship','Temporary','Consultant','Apprenticeship','Trainee'])],
  'work_mode'=>[$req,Rule::in(['On-site','Remote','Hybrid'])],'job_level'=>['nullable','string','max:50'],'country'=>[$req,'string','max:100'],'state'=>['nullable','string','max:100'],'location'=>['nullable','string','max:150'],
  'locations'=>['nullable','array','max:10'],'locations.*.country'=>['nullable','string','max:100'],'locations.*.state'=>['nullable','string','max:100'],'locations.*.city'=>['nullable','string','max:100'],
  'relocation_allowed'=>['boolean'],'vacancies'=>[$req,'integer','min:1','max:10000'],'hiring_urgency'=>['nullable','string','max:50'],'experience_min'=>['nullable','integer','min:0','max:60'],'experience_max'=>['nullable','integer','gte:experience_min','max:60'],
  'education'=>['nullable','string','max:100'],'specialization'=>['nullable','string','max:120'],'primary_technology'=>['nullable','string','max:80'],'required_skills'=>['nullable','string','max:2000'],'mandatory_skills'=>['nullable','string','max:2000'],'good_to_have_skills'=>['nullable','string','max:2000'],
  'description'=>[$req,'string','max:10000'],'salary_type'=>['nullable',Rule::in(['exact','range','undisclosed','competitive'])],'salary_min'=>['nullable','numeric','min:0'],'salary_max'=>['nullable','numeric','gte:salary_min'],'salary_currency'=>['nullable','string','max:10'],'salary_period'=>['nullable','string','max:20'],
  'benefits'=>['nullable','array'],'benefits.*'=>['string','max:100'],'additional_compensation'=>['nullable','array'],'application_method'=>['nullable',Rule::in(['portal','external','email'])],'external_url'=>['nullable','url','max:1000'],'application_email'=>['nullable','email','max:190'],
  'application_deadline'=>['nullable','date','after_or_equal:today'],'job_visibility'=>['nullable',Rule::in(['public','private','internal','draft'])],'resume_required'=>['boolean'],'cover_letter_required'=>['boolean'],'portfolio_required'=>['boolean'],'github_required'=>['boolean'],'linkedin_required'=>['boolean'],
  'questions'=>['nullable','array','max:20'],'questions.*.question'=>['nullable','string','max:1000'],'questions.*.type'=>['nullable','string','max:30'],'questions.*.required'=>['nullable','boolean'],'is_confidential'=>['boolean'],'action'=>['required',Rule::in(['draft','publish'])],
 ];}
}
