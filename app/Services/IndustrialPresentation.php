<?php
namespace App\Services;
use App\Models\IndustrialArea;
use App\Models\IndustrialCompany;
use App\Models\IndustrialSector;
use App\Models\IndustrialState;
use App\Models\IndustrialJob;
class IndustrialPresentation {
 public function build(array $filters,array $taxonomy,$area,$company): array {
  $state=!empty($filters['state'])?IndustrialState::find($filters['state']):null;
  $location=$area?->name??($filters['location']??$state?->name);
  $sector=$taxonomy['sector'];
  $heroText=$company?->name??($sector?($location?$location.' / '.$sector->name:$sector->name):($location??"Where India's factories are hiring"));
  $heroCaption=$area?->tagline??($state?'Industrial and manufacturing economy':'Explore industrial clusters, companies, departments, skills and jobs');
  if($sector)$heroCaption=$taxonomy['processes']->take(8)->pluck('name')->join(' / ')?:$sector->description;
  $areaAsset=$area?->hero_image?\App\Models\IndustrialAsset::where('is_active',true)->where('path',$area->hero_image)->first():null;
  $heroUrl=$sector?$taxonomy['heroUrl']:($area?($areaAsset?->url):$taxonomy['heroUrl']);
  $scope=IndustrialCompany::visible();
  if($state)$scope->whereHas('area',fn($q)=>$q->where('state_id',$state->id));
  if(!empty($filters['location']))$scope->whereHas('area',fn($q)=>$q->atLocation($filters['location']));
  if($area)$scope->where('industrial_area_id',$area->id);
  $allSectors=IndustrialSector::where('is_active',true)->with('assets')->withCount(['companies as company_count'=>fn($q)=>$q->whereIn('industrial_companies.id',(clone $scope)->select('id'))])->orderBy('name')->get();
  $counts=IndustrialJob::live()->whereIn('industrial_jobs.industrial_company_id',(clone $scope)->select('id'))->join('industrial_company_sectors as sector_links','sector_links.industrial_company_id','=','industrial_jobs.industrial_company_id')->selectRaw('sector_links.sector_id, COUNT(*) as total')->groupBy('sector_links.sector_id')->pluck('total','sector_id');
  foreach($allSectors as $item)$item->opening_count=(int)($counts[$item->id]??0);
  $clusterSectors=$area?$allSectors->whereIn('id',$area->sectorCatalog()->where('is_active',true)->pluck('industrial_sectors.id')):collect();
  $browseSectors=$clusterSectors->isNotEmpty()?$clusterSectors:$allSectors;
  $cities=collect();
  if($state&&!$area){$cities=IndustrialArea::visible()->where('state_id',$state->id)->with(['state','sectorCatalog.assets'])->withCount(['companies'=>fn($q)=>$q->visible(),'jobs as openings_count'=>fn($q)=>$q->live()])->orderBy('city')->get()->groupBy(fn($a)=>$a->city?:$a->district);}
  return compact('heroText','heroCaption','heroUrl','browseSectors','clusterSectors','cities');
 }
}
