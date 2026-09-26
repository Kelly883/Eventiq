<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Organizer;
use App\Features\Payouts\Models\Payout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Clean slate for re-runs (file-based DB persists between runs).
DB::table('personal_access_tokens')->truncate();
Payout::query()->delete();
Organizer::query()->delete();
User::query()->whereIn('email', ['orga@test.com','orgb@test.com','reg@test.com','admin@test.com'])->delete();

$orgRole = Role::firstOrCreate(['name' => 'organizer'], ['description'=>'Event organiser','isSystemRole'=>true]);
$adminRole = Role::firstOrCreate(['name' => 'admin'], ['description'=>'Admin','isSystemRole'=>true]);

$userA = User::create(['name'=>'Org A','email'=>'orga@test.com','passwordHash'=>Hash::make('pass'),'role'=>'organizer','role_id'=>$orgRole->id,'emailVerified'=>true]);
$orgA = Organizer::create(['user_id'=>$userA->id,'displayName'=>'Org A','verificationStatus'=>'verified','currency'=>'USD']);
$userB = User::create(['name'=>'Org B','email'=>'orgb@test.com','passwordHash'=>Hash::make('pass'),'role'=>'organizer','role_id'=>$orgRole->id,'emailVerified'=>true]);
$orgB = Organizer::create(['user_id'=>$userB->id,'displayName'=>'Org B','verificationStatus'=>'verified','currency'=>'USD']);
$regular = User::create(['name'=>'Regular','email'=>'reg@test.com','passwordHash'=>Hash::make('pass'),'role'=>'user','emailVerified'=>true]);
$admin = User::create(['name'=>'Admin','email'=>'admin@test.com','passwordHash'=>Hash::make('pass'),'role'=>'admin','role_id'=>$adminRole->id,'emailVerified'=>true]);

function mkPayout($orgId, $status, $amount, $created) {
    // NOTE: created_at/updated_at are NOT in Payout::$fillable, so create()
    // silently drops them and Eloquent stamps them to now(). Use forceFill to
    // set explicit timestamps (mirrors how model factories run unguarded).
    $payout = (new Payout())->forceFill([
        'id'=>Str::uuid(),'organizer_id'=>$orgId,
        'settlement_period_start_date'=>'2025-06-01 00:00:00','settlement_period_end_date'=>'2025-06-15 00:00:00',
        'gross_revenue'=>$amount,'refunds_deducted'=>0,'net_revenue'=>$amount,
        'platform_commission_percentage'=>10,'platform_commission_amount'=>100,
        'processing_fee_percentage'=>2.5,'processing_fee_amount'=>25,
        'tax_withholding_percentage'=>0,'tax_withholding_amount'=>0,
        'payout_amount'=>$amount,'currency'=>'USD','payout_method'=>'bank_transfer',
        'status'=>$status,'completed_at'=>($status==='completed'?now():null),
        'created_at'=>$created,'updated_at'=>$created,
    ]);
    $payout->save();
    return $payout;
}
mkPayout($orgA->id,'completed',5000,'2025-06-10 10:00:00');
mkPayout($orgA->id,'completed',3000,'2025-06-12 10:00:00');
mkPayout($orgA->id,'pending',7000,'2025-06-14 10:00:00');
mkPayout($orgB->id,'completed',99999,'2025-06-10 10:00:00');

file_put_contents('/tmp/tokens.env',
    "ORG_A_TOKEN=".$userA->createToken('api-token')->plainTextToken."\n".
    "ORG_B_TOKEN=".$userB->createToken('api-token')->plainTextToken."\n".
    "REGULAR_TOKEN=".$regular->createToken('api-token')->plainTextToken."\n".
    "ADMIN_TOKEN=".$admin->createToken('api-token')->plainTextToken."\n"
);
echo "Seeded. Tokens written to /tmp/tokens.env\n";
echo "Users: orgA={$userA->id} orgB={$userB->id} regular={$regular->id} admin={$admin->id}\n";
