<?php
namespace Database\Seeders;
use App\Models\InterviewCompany;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
class InterviewCompanySeeder extends Seeder {
 public function run():void {
  $groups=[
   ['tier_1','Technology',['Google','Microsoft','Amazon','Meta','Apple','Netflix','Adobe','NVIDIA','Salesforce','Oracle','Uber','Airbnb','LinkedIn','Atlassian','ServiceNow','Intuit','PayPal','Stripe','Spotify','Twitter / X']],
   ['tier_1','Indian Product',['Flipkart','Myntra','PhonePe','Razorpay','Zomato','Swiggy','Meesho','Dream11','CRED','Groww','Zerodha','Freshworks','Zoho','Ola','Paytm','InMobi','BrowserStack','Postman']],
   ['tier_2','IT Services',['TCS','Infosys','Wipro','HCLTech','Tech Mahindra','Cognizant','Accenture','Capgemini','LTIMindtree','Mphasis','Persistent Systems','Coforge','Hexaware','Birlasoft','NTT DATA','DXC Technology']],
   ['tier_1','Banking & Finance',['JPMorgan Chase','Goldman Sachs','Morgan Stanley','American Express','Wells Fargo','Barclays','HSBC','Deutsche Bank','Citi','UBS','BNY','BlackRock','Visa','Mastercard']],
   ['tier_2','Consulting',['Deloitte','EY','PwC','KPMG','McKinsey & Company','Boston Consulting Group','Bain & Company']],
   ['tier_2','Automotive',['Tata Motors','Mahindra & Mahindra','Maruti Suzuki','Hyundai Motor India','Honda','Toyota','Bajaj Auto','TVS Motor','Ashok Leyland','Bosch','Mercedes-Benz','BMW','Volkswagen','Volvo','Tesla']],
   ['tier_2','Manufacturing',['Siemens','GE','Honeywell','Schneider Electric','ABB','Caterpillar','John Deere','Cummins','Larsen & Toubro','Tata Steel','JSW Steel','Reliance Industries','Adani Group']],
   ['tier_2','Telecom',['Reliance Jio','Bharti Airtel','Vodafone Idea','Ericsson','Nokia','Cisco','Qualcomm']],
   ['tier_2','Retail & Consumer',['Walmart','Target','IKEA','Unilever','Procter & Gamble','Nestlé','PepsiCo','Coca-Cola','Nike','Adidas']],
   ['tier_2','Pharma & Healthcare',['Pfizer','Johnson & Johnson','Novartis','Roche','AstraZeneca','Sun Pharma','Dr. Reddy’s Laboratories','Cipla']],
   ['tier_2','Aviation',['Boeing','Airbus','IndiGo','Air India','Emirates']],
  ];
  foreach($groups as [$tier,$industry,$names]) foreach($names as $name) InterviewCompany::updateOrCreate(['slug'=>Str::slug($name)],['name'=>$name,'tier'=>$tier,'industry'=>$industry,'country'=>'India','short_description'=>'Interview experiences reported for '.$name.'.','is_featured'=>$tier==='tier_1','is_active'=>true]);
 }
}
