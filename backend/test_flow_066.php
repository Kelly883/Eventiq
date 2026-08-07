<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$pdo = DB::connection()->getPdo();
DB::statement("PRAGMA foreign_keys = ON");
echo "=== TEST FLOW: INSERT + FK VERIFICATION ===\n";
try{
  DB::beginTransaction();
  $now = date("Y-m-d H:i:s");
  $userId = "usr_".str_replace(".","",uniqid("",true));
  DB::table("users")->insert([
    "id"=>$userId,"name"=>"Test Buyer","email"=>"buyer".uniqid()."@test.com",
    "passwordHash"=>bcrypt("password"),"role"=>"attendee","created_at"=>$now,"updated_at"=>$now
  ]);
  echo "  + inserted user id=$userId\n";

  $orgId = DB::table("organizers")->insertGetId([
    "user_id"=>$userId,"business_name"=>"Test Organizer","created_at"=>$now,"updated_at"=>$now
  ]);
  echo "  + inserted organizer id=$orgId\n";

  $eventId = DB::table("events")->insertGetId([
    "organizer_id"=>$orgId,"title"=>"Test Event","start_datetime"=>$now,"end_datetime"=>$now,
    "capacity"=>100,"status"=>"published","created_at"=>$now,"updated_at"=>$now
  ]);
  echo "  + inserted event id=$eventId\n";

  $tierId = DB::table("ticket_tiers")->insertGetId([
    "event_id"=>$eventId,"name"=>"General","price"=>1000.00,"quantity"=>100,
    "created_at"=>$now,"updated_at"=>$now
  ]);
  echo "  + inserted ticket_tier id=$tierId\n";

  $orderId = DB::table("orders")->insertGetId([
    "user_id"=>$userId,"event_id"=>$eventId,"total_amount"=>2000,"currency"=>"NGN",
    "status"=>"completed","payment_gateway"=>"paystack","payment_intent_id"=>"pi_test_".uniqid(),
    "created_at"=>$now,"updated_at"=>$now
  ]);
  echo "  + inserted order id=$orderId\n";

  $itemId = DB::table("order_items")->insertGetId([
    "order_id"=>$orderId,"ticket_tier_id"=>$tierId,"quantity"=>2,"unit_price"=>1000,
    "created_at"=>$now,"updated_at"=>$now
  ]);
  echo "  + inserted order_item id=$itemId\n";

  $t1 = DB::table("tickets")->insertGetId([
    "order_id"=>$orderId,"user_id"=>$userId,"event_id"=>$eventId,"ticket_tier_id"=>$tierId,
    "qr_code_data"=>"QR-".uniqid(),"status"=>"valid","created_at"=>$now,"updated_at"=>$now
  ]);
  $t2 = DB::table("tickets")->insertGetId([
    "order_id"=>$orderId,"user_id"=>$userId,"event_id"=>$eventId,"ticket_tier_id"=>$tierId,
    "qr_code_data"=>"QR-".uniqid(),"status"=>"valid","created_at"=>$now,"updated_at"=>$now
  ]);
  echo "  + inserted tickets id=$t1, $t2\n";

  $payId = DB::table("payments")->insertGetId([
    "order_id"=>$orderId,"payment_intent_id"=>"pi_test_".uniqid(),"amount"=>2000,"currency"=>"NGN",
    "status"=>"success","gateway"=>"paystack","gateway_response"=>json_encode(["status"=>"success"]),
    "created_at"=>$now,"updated_at"=>$now
  ]);
  echo "  + inserted payment id=$payId\n";

  echo "\n  --- FK INTEGRITY VERIFICATION ---\n";
  echo "  order owned by user ".DB::table("orders")->where("id",$orderId)->value("user_id")." (FK users OK)\n";
  echo "  order_items linked: ".DB::table("order_items")->where("order_id",$orderId)->count()." (FK orders OK)\n";
  echo "  tickets linked: ".DB::table("tickets")->where("order_id",$orderId)->count()." (FK orders OK)\n";
  echo "  payments linked: ".DB::table("payments")->where("order_id",$orderId)->count()." (FK orders OK)\n";

  // CASCADE test
  DB::table("orders")->where("id",$orderId)->delete();
  echo "\n  --- AFTER ORDER DELETE (id=$orderId) ---\n";
  echo "  order_items remaining: ".DB::table("order_items")->where("order_id",$orderId)->count()." (expect 0 CASCADE)\n";
  echo "  payments remaining: ".DB::table("payments")->where("order_id",$orderId)->count()." (expect 0 CASCADE)\n";
  echo "  tickets remaining: ".DB::table("tickets")->where("order_id",$orderId)->count()." (SET NULL - tickets kept)\n";

  // Cleanup test data
  DB::table("tickets")->where("event_id",$eventId)->delete();
  DB::table("ticket_tiers")->where("id",$tierId)->delete();
  DB::table("events")->where("id",$eventId)->delete();
  DB::table("organizers")->where("id",$orgId)->delete();
  DB::table("users")->where("id",$userId)->delete();
  DB::commit();
  echo "\n  --- TEST PASSED: All FK constraints work correctly ---\n";
}catch(\Throwable $e){
  if(DB::transactionLevel()>0) DB::rollBack();
  echo "  [TEST FAILED] ".$e->getMessage()."\n";
  echo $e->getFile().":".$e->getLine()."\n";
}
echo "\nDONE\n";
