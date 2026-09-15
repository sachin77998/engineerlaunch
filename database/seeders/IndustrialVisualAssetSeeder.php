<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\IndustrialAsset;
use App\Models\IndustrialSector;
use App\Models\IndustrialSubsector;
use App\Models\IndustrialProcess;
class IndustrialVisualAssetSeeder extends Seeder {
 public function run(): void {
  foreach(IndustrialAsset::with('sector')->get() as $asset)$asset->update(['asset_type'=>$asset->path===$asset->sector->hero_image?'hero':'card','title'=>$asset->sector->name,'image_url'=>$asset->path,'alt_text'=>$asset->alt]);
  $images=['textiles'=>'textiles/hero.png','electronics'=>'electronics/hero.png','pharmaceuticals'=>'pharma/hero.png','medical-devices'=>'pharma/hero.png','cnc-machining'=>'gear/gear6.jpeg','automobile'=>'automobile/hero.png','auto-components'=>'axle/axle2.jpeg','warehouse-logistics'=>'logistics/hero.png','tyres'=>'tyres/hero.png'];
  foreach($images as $slug=>$file){$path='images/industries/'.$file;if(!is_file(public_path($path)))continue;$sector=IndustrialSector::where('slug',$slug)->firstOrFail();$sector->update(['hero_image'=>$path]);
   // A path may represent several sectors; copy the same visual to a sector-owned sibling path.
   $owned='images/industries/'.$slug.'-hero.'.pathinfo($path,PATHINFO_EXTENSION);
   if(!is_file(public_path($owned)))copy(public_path($path),public_path($owned));
   $sector->update(['hero_image'=>$owned]);
   IndustrialAsset::updateOrCreate(['path'=>$owned],['sector_id'=>$sector->id,'asset_type'=>'hero','title'=>$sector->name,'image_url'=>$owned,'alt'=>$sector->name.' category illustration','alt_text'=>$sector->name.' category image','source_name'=>str_ends_with($file,'.png')?'AI-generated illustrative industry scene':'User-supplied category photograph']);
  }
  foreach(IndustrialSector::with('assets')->get() as $sector){
   $cards=$sector->assets->where('asset_type','card')->values();
   if($cards->count()>=3){$cards->last()->update(['asset_type'=>'thumbnail']);$cards[$cards->count()-2]->update(['asset_type'=>'banner']);}
  }
  foreach(['Gears'=>'gear-manufacturing','Axle'=>'axle-manufacturing','Brake Disc'=>'braking-systems','Brake Caliper'=>'braking-systems','Brake Pads'=>'braking-systems'] as $name=>$slug)IndustrialSubsector::where('name',$name)->update(['visual_sector_id'=>IndustrialSector::where('slug',$slug)->value('id')]);
  $sector=IndustrialSector::where('slug','textiles')->firstOrFail();
  foreach(['Spinning','Knitting','Dyeing','Weaving','Garment Manufacturing','Woollens','Textile Testing','Packing'] as $order=>$name){$process=IndustrialProcess::firstOrCreate(['slug'=>\Illuminate\Support\Str::slug($name)],['name'=>$name,'is_active'=>true]);$sub=IndustrialSubsector::firstOrCreate(['slug'=>'textiles-'.\Illuminate\Support\Str::slug($name)],['sector_id'=>$sector->id,'name'=>$name,'is_active'=>true]);$sub->processes()->syncWithoutDetaching([$process->id=>['sort_order'=>$order]]);}
  $electronics=IndustrialSector::where('slug','electronics')->firstOrFail();$sub=IndustrialSubsector::firstOrCreate(['slug'=>'electronics-semiconductor-esdm'],['sector_id'=>$electronics->id,'name'=>'Semiconductor / ESDM','is_active'=>true]);
  foreach(['Semiconductor Assembly','Semiconductor Testing','Electronics Assembly'] as $order=>$name){$process=IndustrialProcess::firstOrCreate(['slug'=>\Illuminate\Support\Str::slug($name)],['name'=>$name,'is_active'=>true]);$sub->processes()->syncWithoutDetaching([$process->id=>['sort_order'=>$order]]);}
 }
}
