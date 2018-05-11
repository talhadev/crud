<?php

namespace App\Console\Commands;
use App\Http\Controllers\Api\ApiCronController;
use Illuminate\Console\Command;
use App\Models\Shipping;

class UpdateCourierStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UpdateCourierStatus:updatecourierstatus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update courier status';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $filter = ['leopard' ];
        $order_ids = Shipping::select('order_id', 'courier_name','store_id')->where('courier_status','!=','Delivered')->where('courier_name',$filter)->get();
        foreach($order_ids as $ids){

            $courier_name = $ids->courier_name;
            $order_id     = $ids->order_id;
            $store_id     = $ids->store_id;

            $cron_name = strtolower('cron'.$courier_name);
            $cronController= new ApiCronController();
            if(strtolower(method_exists($cronController,$cron_name))){
                $cronController->$cron_name($order_id,$store_id,$courier_name);

            }
        }
    }
}
