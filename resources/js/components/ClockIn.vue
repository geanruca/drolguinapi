<?php

namespace App\Http\Controllers\Web;

use DB;

use App\Logs;
use Response;
use App\DRIVER;
use App\TERMINAL;

use Carbon\Carbon;
use App\CarrierLog;
use App\GTL_TIME_LOG;
use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class ClockInController extends Controller
{
	public function clockincrud(){
		return view('driverapi.logs.clockincrud', [
			'title'        => 'Logs Clock In/Out',
			'log_title'    => 'Logs Clock In/Out',
			"searchText"   => '',
			'active_menus' => ['Logs_Apps'],
		]);
	}


	/**
	 *  1 terminal filer
	 */
	public function TerminalFilter(Request $req) {
	
		$terminals = TERMINAL::where('terminal_zone', '<>', 'AGENT')->select('TERMINAL_ZONE')->get();
		return response()->json(['status' => count($terminals) > 0 , 'terminals' => $terminals]);
	}

	/**
	 * 	2.- Pay Period Filter
	 * 	 * 
	 * 
	 */

	 public function PayPeriodFilter(Request $req) {
		$lisperiod = DB::connection('odbc')->table('GTL_PAY_PERIODS')->whereRaw('period_start < current_date')
		->selectRaw("period || '. ' || varchar_format(period_start,'MM/DD/YYYY') || ' - ' || varchar_format(period_end,'MM/DD/YYYY')")
		->orderByDesc('YEAR')->orderByDesc('period')
		->get()->pluck("1");
		// dd($lisperiod);
								//    ->selectRaw("period || '. ' || varchar_format(period_start,'MM/DD/YYYY') || ' - ' || varchar_format(period_end,'MM/DD/YYYY')")
			return response()->json(['status' => count($lisperiod) > 0 , 'terminals' => $lisperiod]);
	 }

	 /**
	  *  3.- 
	  */
	  public function DriverList($ds, $de, $terminal) {
		
		// $inicio = new Carbon();
		// $inicio->parse($ds);
		// dd($ds);
		$ds = str_replace("-", "/", $ds);
		$de = str_replace("-", "/", $de);
		$ds = Carbon::parse($ds)->format('Y-m-d');
		$de = Carbon::parse($de)->format('Y-m-d');
		
		
		
		 
		$driverList = DB::connection('odbc')->table('GTL_ACTIVE_DRIVERS x')->join('driver a', 'x.driver_id' , '=', 'a.driver_id')->whereRaw('x.active between BEG_OF_DAY(?) and END_OF_DAY(?)',[$ds, $de])->where('x.terminal_zone' ,'like',$terminal.'%')
		->where('x.export_code' ,'LOC')
		->where('x.other_code', 'NOT LIKE','O/%')
		->selectRaw("GTL_BEG_OF_PAY_PERIOD(x.active) period,
		a.driver_id,
		a.name,
		(SELECT coalesce(sum(round((cast(timestampdiff(2,enddate - startdate) as double) / 60) / 60,3)),0) FROM GTL_TIME_LOG WHERE RESOURCE_TYPE = 'D' AND RESOUCE_ID = A.DRIVER_ID AND STARTDATE BETWEEN BEG_OF_DAY(?) and END_OF_DAY(?)) HRS",[$ds, $de])
		->groupBy('GTL_BEG_OF_PAY_PERIOD(x.active)')
		->groupBy('a.driver_id')
		->groupBy('a.name')		
		->get();

		return response()->json(['driverlist' => $driverList]);
		  									 

	  }

	  /**
	   *  4 Hours List
	   */
	  public function hoursList($driver, $ds, $de) {

		$ds = str_replace("-", "/", $ds);
		$de = str_replace("-", "/", $de);
		$ds = Carbon::parse($ds)->format('m/d/Y');
		$de = Carbon::parse($de)->format('m/d/Y');
		
		  $hourslist = DB::connection('odbc')->table('driver a')
			  ->join('gtl_time_log b' , function($join) {

				 $join->on('a.DRIVER_ID', '=', 'b.resouce_id');})

				 ->where('a.driver_id', $driver)
				 ->whereRaw("date(b.startdate) between beg_of_day(?) and end_of_day(?)",[$ds, $de])
				 ->where('a.export_code', 'LOC')
				 ->where('a.other_code', 'NOT LIKE', 'O/%') 
				 ->where('b.resource_type',  'D')
				 ->orderBy('b.startdate')
				//  (select distmeter from rm_mile where loctype = 'PU' and loccode = a.default_punit and curdate < b.startdate order by curdate desc fetch first row only) start_odom,
				//  (select distmeter from rm_mile where loctype = 'PU' and loccode = a.default_punit and curdate >= b.enddate order by curdate fetch first row only) end_odom,
				 ->selectRaw("b.id,
				 a.driver_id,
				 a.default_punit truck,
				 b.startdate,
				 b.enddate,
				 round((cast(timestampdiff(2,enddate - startdate) as double) / 60) / 60,3) hrs,
				 b.start_location,
				 b.end_location,
				 b.approved_by,
				 b.approved,
				 (select count(distinct bill_number) from tlorder where pick_up_driver = a.driver_id and actual_pickup between b.startdate and coalesce(b.enddate,end_of_week(b.startdate))) picks,
				 (select count(distinct bill_number) from tlorder where delivery_driver = a.driver_id and actual_delivery between b.startdate and coalesce(b.enddate,end_of_week(b.startdate))) drops,
				 case when (select distmeter from rm_mile where loctype = 'PU' and loccode = a.default_punit and curdate >= b.enddate order by curdate fetch first row only) is not null then (select distmeter from rm_mile where loctype = 'PU' and loccode = a.default_punit and curdate >= b.enddate order by curdate fetch first row only) - (select distmeter from rm_mile where loctype = 'PU' and loccode = a.default_punit and curdate < b.startdate order by curdate desc fetch first row only) end miles")
				 ->get();
				 return response()->json(['status' => count($hourslist) > 0, 'driverlist' => $hourslist]);
	  }

	public function index(Request $request){
		$a = GTL_TIME_LOG::orderBy('STARTDATE','desc')
		->join('DRIVER as D','D.DRIVER_ID','RESOUCE_ID')
		->select('D.NAME as DRIVER_NAME',
				'GTL_TIME_LOG.ID',
				'GTL_TIME_LOG.RESOURCE_TYPE',
				'GTL_TIME_LOG.RESOUCE_ID',
				'GTL_TIME_LOG.PAY_TYPE',
				'GTL_TIME_LOG.STARTDATE',
				'GTL_TIME_LOG.START_LOCATION',
				'GTL_TIME_LOG.ENDDATE',
				'GTL_TIME_LOG.END_LOCATION',
				'GTL_TIME_LOG.UPDATED_BY',
				'GTL_TIME_LOG.UPDATED',
				'GTL_TIME_LOG.APPROVED_BY',
				'GTL_TIME_LOG.APPROVED',
				'GTL_TIME_LOG.PROCESSED',
				'GTL_TIME_LOG.PAY_ID',
				'GTL_TIME_LOG.ROW_TIMESTAMP'
		)->get();
		
		//Terminales
		$b = TERMINAL::distinct('TERMINAL_ZONE')->where('TERMINAL_ZONE','<>','AGENT')->get();
		
		return response()->json([
			'status'     => true,
			'poncheos'   => $a,
			'terminales' => $b
		]);
	}
	public function format_datetime($fecha){
		$fecha = str_replace(' AM','',$fecha);
		if (strrpos($fecha, 'PM') !== false) {
			$fecha = str_replace(' PM','',$fecha);
			if (strlen($fecha) == 25) {
				$hour = substr($fecha, 11,1);
				$hour_mod = $hour + 12;
				$fecha = str_replace(' '.$hour,' '.$hour_mod,$fecha);
				// \Log::info('FECHA_CTM_PM', ['hour' => $hour, 'hour_mod' => $hour_mod, 'req->PHONEDATE' => $fecha]);
			} else {
				$remp = substr($fecha, 11,2);
				if (substr($remp, 0,1) == 0) {
					$hour = substr($remp, 1,1);
				} else {
					$hour = $remp;
				}

				$hour_mod = $hour + 12;
				$fecha = str_replace(' '.$remp,' '.$hour_mod,$fecha);
				// \Log::info('FECHA_CTM_PM', ['hour' => $hour, 'hour_mod' => $hour_mod, 'req->PHONEDATE' => $fecha]);
			}

		}
		$date_ctm  =  date_create($fecha);
		$fecha = date_format($date_ctm, 'Y-m-d H:i:s.000000');
		return $fecha;
	}
	public function update(Request $r){
		// agregar un log de update aqui
		$user = Auth::user();
		// dd($user);
		//$r->STARTDATE = $this->format_datetime($r->STARTDATE);
		//$r->ENDDATE   = $this->format_datetime($r->ENDDATE);
		$r->UPDATED   = $this->format_datetime(Carbon::now());
		$ds = $r->STARTDATE;
		$de = $r->ENDDATE;
		$ds = str_replace("-", "/", $ds);
		$de = str_replace("-", "/", $de);
		$ds = $this->format_datetime($ds);
		$de = $this->format_datetime($de);
		
		//$ds = Carbon::parse($ds)->format('m/d/Y');
		//$de = Carbon::parse($de)->format('m/d/Y');
		// saneamiento de fecha
		//validar que la fecha de término es mayor a la fecha de inicio
		
		if($ds > $de){
			return response()->json('Error. End Date must be superior than Start Date',400);
		}
		
		$a = DB::connection('odbc')->table('GTL_TIME_LOG')->where("ID", $r->ID);
		// DB::connection('odbc')->table('GTL_TIME_LOG')->insert
		// dd($a);
		$a->update([
			'STARTDATE'      => $ds,
			//'START_LOCATION' => $r->START_LOCATION,
			'ENDDATE'        => $de,
			//'END_LOCATION'   => $r->END_LOCATION,
			'UPDATED_BY'     => $user->username,
			'UPDATED'        => $r->UPDATED
		]);

		return response()->json('updated!');
	}
	public function delete($ID){
		$a = DB::connection('odbc')->table('GTL_TIME_LOG')->where("ID", $ID)->delete();
		return response()->json('deleted!');
	}

	//http://192.168.1.134:90/driverapi/api/v3/DriverClocks/insert
	//DRIVER_ID:BARTLOMIE 
	//PHONE_DATETIME:2020-04-21 9:39:03.000000
	//LOCATION:NLTERM
	
	public function clockin_today(Request $request){
		$hoy 	= Carbon::today();
		$ahora 	= Carbon::now();
		$a = Logs::where("app", "go2driver")
		->where('result','like','%Clocked%')
		->whereBetween('date',[$hoy, $ahora])
		->orderBy('date','desc')->get();

		return response()->json($a);
	}
	public function clockin_filter(Request $r){

		// $l = App\Logs::first();
		// dd($l);
        $from = Carbon::parse($r->since);
		$to   = Carbon::parse($r->until)->addDays(1);
		// $result = Logs::where("app", "go2driver")->orderBy('date','desc')->get();
		// dd(Logs::first());

        $query = Logs::query();
		if(!empty($r->search)){
            $query = $query->where( function($query) use ($r) {
				$query->where('params','like','%'.$r->search.'%')
				->orWhere('USER','like','%'.$r->search.'%')
				->orWhere('action','like','%'.$r->search.'%')
				->orWhere('level','like','%'.$r->search.'%')
				->orWhere('result','like','%'.$r->search.'%');
			});
        }
        if(!empty($r->type) && $r->type <> 'all'){
            $query = $query->where('level',$r->type);
        }
        if($r->since){
            $query = $query->where('date','>=',$from);
        }

        if($r->until){
            $query = $query->where('date','<=',$to);
        }
		$a = $query->where('app','go2driver')
		->where('result','like','%Clocked%')
		->orderBy('date','desc')->get();

        return response()->json($a);
	}

	/**
	 *  Approve : call proc GTL_TIME_LOG_APPROVE
	 */
	public function approve(Request $r){
			
		$messages = [
				'id.required'      => 'Errors id is required',
				'username.required' => 'Username is required'
			];

		// Validation
		$validator =  Validator::make($r->all(), [
			'id'      => 'required',
			'username' => 'required'
		], $messages);

		if ($validator->fails()) {
			$errors = $validator->errors();
			return response()->json(['status' => false, "msg" =>  $errors->toJson()]);
		} 
		
		\Log::debug(['DRIVER-CLOCK', ['APPROVE_ID' => $r->id, 'username' => $r->username]]);

		$oResult = "";
		$id = $r->id;
		$user = $r->username;

        $db = DB::connection('odbc')->getPdo();

        $stmt = $db->prepare("CALL TMWIN.GTL_TIME_LOG_APPROVE(?, ? , ?) ");

		$stmt->bindParam(1, $id);       		 
		$stmt->bindParam(2,$oResult,\PDO::PARAM_STR|\PDO::PARAM_INPUT_OUTPUT, 80);
		$stmt->bindParam(3,$user);
        
		$stmt->execute();
		
		\Log::debug('DIVER-LOCK', ['RESULT-PROC' => $oResult]);

		if ((!empty($oResult)) && ($oResult != 'SUCCESS')) {

			return response()->json(['stautus' => false, "msg" => $oResult]);			

		}
	
		\Log::debug(['DRIVER-CLOCK',['APPROVED' => 'SUCCESS']]);

		return response()->json(['status' => true, 'msg' => 'Approved']);
	}

	public function unapprove(Request $r){

		$messages = [
				'id.required'      => 'Errors id is required',
				'username.required' => 'Username is required'
			];

		// Validation
		$validator =  Validator::make($r->all(), [
			'id'      => 'required',
			'username' => 'required'
		], $messages);

		if ($validator->fails()) {
			$errors = $validator->errors();
			return response()->json(['status' => false, "msg" =>  $errors->toJson()]);
		} 
		
		\Log::debug(['DRIVER-CLOCK', ['UNAPPROVE_ID' => $r->id, 'username' => $r->username]]);

		$oResult = "";
		$id = $r->id;

        $db = DB::connection('odbc')->getPdo();

        $stmt = $db->prepare("CALL TMWIN.GTL_TIME_LOG_UNAPPROVE(?, ? ) ");

        $stmt->bindParam(1, $id);        
        $stmt->bindParam(2,$oResult,\PDO::PARAM_STR|\PDO::PARAM_INPUT_OUTPUT, 80);
        
		$stmt->execute();
		
		\Log::debug('DIVER-LOCK', ['RESULT-PROC' => $oResult]);

		if ((!empty($oResult)) && ($oResult != 'SUCCESS.')) {

			return response()->json(['stautus' => false, "msg" => $oResult]);			

		}
	
		\Log::debug(['DRIVER-CLOCK',['UNAPPROVE' => 'SUCCESS']]);

		return response()->json(['status' => true, 'msg' => 'UNAPPROVE']);
	}
	
	public function insert(Request $r){
        
        $messages = [
            'DRIVER_ID.required'      => 'Errors DRIVER_ID is required',
            'PHONE_DATETIME.required' => 'Errors PHONE_DATETIME is required',
            'LOCATION.required'       => 'Errors LOCATION is required',
        ];
        // Validation
        $validator =  Validator::make($r->all(), [
            'DRIVER_ID'      => 'required',
            'PHONE_DATETIME' => 'required',
            'LOCATION'       => 'required',
        ], $messages);

        if ($validator->fails()) {
			$errors = $validator->errors();
            return response()->json(['status' => false, "msg" =>  $errors->toJson()]);
        } 
		
		
        $driver = DRIVER::where('DRIVER_ID',$r->DRIVER_ID)->first();
        // Validar tipo de driver
        if(!$driver){
		return response()->json([
			'status' => false,
            'msg'    => 'Driver does not exist'
			]);
		}
			
		if($driver->TMT_DRIVER <> 'True'){
		return response()->json([
			'status' => false,
			'msg'    => 'TMT_DRIVER must be True'
			]);
		}
			
			Logs::InsertLog('DriverClockController insert start', 'info', 'go2driver', 
			'RESOUCE_ID: '     	 . $r->DRIVER_ID.
			', STARTDATE: '      . $r->PHONE_DATETIME.
			', START_LOCATION: ' . $r->LOCATION.
			', ENDDATE: '      	 . $r->END_DATETIME.
			', END_LOCATION: '	 . $r->END_LOCATION.
			', UPDATED_BY: '     . $r->DRIVER_ID.
			', UPDATED: '.Carbon::now(), 'Driver ID: '.$r->DRIVER_ID, "Poncheo");
			//server date
			//$fecha = date("Y-m-d H:i:s.000000");
			
			
			$r->PHONE_DATETIME = str_replace(' AM','',$r->PHONE_DATETIME);
			if (strrpos($r->PHONE_DATETIME, 'PM') !== false) {
				$r->PHONE_DATETIME = str_replace(' PM','',$r->PHONE_DATETIME);
				if (strlen($r->PHONE_DATETIME) == 25) {
					$hour = substr($r->PHONE_DATETIME, 11,1);
					$hour_mod = $hour + 12;
					$r->PHONE_DATETIME = str_replace(' '.$hour,' '.$hour_mod,$r->PHONE_DATETIME);
					// \Log::info('FECHA_CTM_PM', ['hour' => $hour, 'hour_mod' => $hour_mod, 'req->PHONEDATE' =>$r->PHONE_DATETIME]);
				} else {
					$remp = substr($r->PHONE_DATETIME, 11,2);
					if (substr($remp, 0,1) == 0) {
						$hour = substr($remp, 1,1);
					} else {
						$hour = $remp;
					}
					
					$hour_mod = $hour + 12;
					$r->PHONE_DATETIME = str_replace(' '.$remp,' '.$hour_mod,$r->PHONE_DATETIME);
					// \Log::info('FECHA_CTM_PM', ['hour' => $hour, 'hour_mod' => $hour_mod, 'req->PHONEDATE' =>$r->PHONE_DATETIME]);
				}
				
			}
			
			$date_ctm  =  date_create($r->PHONE_DATETIME);
			$r->PHONE_DATETIME = date_format($date_ctm, 'Y-m-d H:i:s.000000');
				
			$driver_ya_poncho = GTL_TIME_LOG::where('RESOUCE_ID',$r->DRIVER_ID)
			->whereNotNull('STARTDATE')
			->whereNull('ENDDATE')
			// ->get();
			->first();
				
				// dd($driver_ya_poncho);
				
			//if($driver_ya_poncho){
			//	DB::connection('odbc')->table('GTL_TIME_LOG')
			//	->where('ID', $driver_ya_poncho->ID)
			//	->update([
            //		'ENDDATE'      => $r->PHONE_DATETIME,
            //		'UPDATED'      => $r->PHONE_DATETIME,
            //		'END_LOCATION' => $r->LOCATION,
			//	]);
			//		
			//	Logs::InsertLog('DriverClockController Clocked out', 'info', 'go2driver',
			//	'RESOUCE_ID: '     . $r->DRIVER_ID.
            //	', STARTDATE: '      . $r->PHONE_DATETIME.
            //	', START_LOCATION: ' . $r->LOCATION.
            //	//', ENDDATE: '      	 . $r->END_DATETIME.
            //	', END_LOCATION: '	 . $r->END_LOCATION.
            //	', UPDATED_BY: '     . $r->DRIVER_ID.
			//	', UPDATED: '.Carbon::now(),
			//	 'Driver ID: '.$r->DRIVER_ID, 'Clocked out!');
            //	return response()->json([
            //	    'status' => true,
            //	    'msg'    => 'Clocked out!'
            //	]);
        	//}
		
        // V = C 
        $PAY_TYPE = 'P';
        if($driver->PAY_TYPE == 'V'){
            $PAY_TYPE = 'C';
		}
		
		$ds = $r->PHONE_DATETIME;
		$ds = str_replace("-", "/", $ds);
		$ds = $this->format_datetime($ds);
		if(isset($r->END_DATETIME)){
			$de = $r->END_DATETIME;
			$de = str_replace("-", "/", $de);
			$de = $this->format_datetime($de);
			
			DB::connection('odbc')->table('GTL_TIME_LOG')->insert([
				'ID'             => DB::connection('odbc')->raw("TMWIN.GET_GEN_ID('GEN_GTL_TIME_LOG_ID')"),
				'RESOURCE_TYPE'  => 'D',
				'RESOUCE_ID'     => $r->DRIVER_ID,
				'PAY_TYPE'       => $PAY_TYPE,
				'STARTDATE'      => $ds,
				'START_LOCATION' => $r->LOCATION,
				'ENDDATE'      	 => $de,
				'END_LOCATION'	 => $r->END_LOCATION,
				'UPDATED_BY'     => $r->DRIVER_ID,
				'PROCESSED'      => 'False',
				
			]);
			
				
			Logs::InsertLog('DriverClockController Clocked in', 'info', 'go2driver',
				'RESOUCE_ID: '     . $r->DRIVER_ID.
				', STARTDATE: '      . $r->PHONE_DATETIME.
				', START_LOCATION: ' . $r->LOCATION.
				', ENDDATE: '      	 . $r->END_DATETIME.
				', END_LOCATION: '	 . $r->END_LOCATION.
				', UPDATED_BY: '     . $r->DRIVER_ID.
				', UPDATED: '.Carbon::now()
			,  'Driver ID: '.$r->DRIVER_ID, 'Clocked in!');
			
			return response()->json([
				'status' => true,
				'msg'    => 'Clocked in!'
			]); 
		}
		
        //hago insert clock in
        DB::connection('odbc')->table('GTL_TIME_LOG')->insert([
            'ID'             => DB::connection('odbc')->raw("TMWIN.GET_GEN_ID('GEN_GTL_TIME_LOG_ID')"),
            'RESOURCE_TYPE'  => 'D',
            'RESOUCE_ID'     => $r->DRIVER_ID,
            'PAY_TYPE'       => $PAY_TYPE,
            'STARTDATE'      => $ds,
            'START_LOCATION' => $r->LOCATION,
            //'ENDDATE'      	 => $de,
            'END_LOCATION'	 => $r->END_LOCATION,
            'UPDATED_BY'     => $r->DRIVER_ID,
            'PROCESSED'      => 'False',
            
		]);
		
			
		Logs::InsertLog('DriverClockController Clocked in', 'info', 'go2driver',
			'RESOUCE_ID: '     . $r->DRIVER_ID.
            ', STARTDATE: '      . $r->PHONE_DATETIME.
            ', START_LOCATION: ' . $r->LOCATION.
            //', ENDDATE: '      	 . $r->END_DATETIME.
            ', END_LOCATION: '	 . $r->END_LOCATION.
            ', UPDATED_BY: '     . $r->DRIVER_ID.
			', UPDATED: '.Carbon::now()
		,  'Driver ID: '.$r->DRIVER_ID, 'Clocked in!');
		
        return response()->json([
            'status' => true,
            'msg'    => 'Clocked in!'
        ]); 

    }
}