<?php namespace App\Http\Controllers;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use App\Models\BetResult;
use App\Models\Account;
use App\Models\Match;
use App\Models\ScheduleBet;
use App\Models\MatchBetDetail;
use Session;
use DB;

class ReportController extends Controller {

	public static $provider ;
	public static $account_id ;

	public function __construct()
	{	
			
		$this->middleware('admin');
		
		self::$provider = session('provider')!= NULL ? session('provider') : 2;
		self::$account_id = session('account_id');
		
		$accountArr = Account::where('provider', self::$provider)->where('status', 1)->get();		

		view()->share([ 'accountArr' => $accountArr, 'provider' => self::$provider ]);


	}	
	public function statement(Request $request)
	{	
		$ondate = $request->ondate ? $request->ondate : date('m/d/Y');	
		
		$dataArr = BetResult::where(['bet_date' => $ondate, 'provider' => self::$provider, 'user_id' => self::$account_id])->get();
		
		return view('back.report.statement', compact('dataArr', 'ondate'));
	}	
	public function viewLog(Request $request){
		$schedule_id = $request->schedule_id;
		$s = ScheduleBet::find($schedule_id);	
		$haveLog = MatchBetDetail::where([
			'half' => $s->time_half,
			'user_id' => self::$account_id,
			'ref_id' => $s->match_id])->count();		
		$dataArr = MatchBetDetail::where([
			'half' => $s->time_half,
			'user_id' => self::$account_id,
			'ref_id' => $s->match_id,
			'bet_type' => $s->bet_type,
			'bet_ratio' => $s->ratio
			])->where('minute', '>=', $s->time_from)->where('minute', '<=', $s->time_to)->get();
		$account_id = self::$account_id;
		return view('back.report.view-log', compact('dataArr', 's', 'haveLog', 'account_id'));
	}
	public function reportSchedule(Request $request)
	{			
		$detailMatch = [];
		$ondate = $request->ondate ? $request->ondate : date('Y-m-d');
		$status = $request->status ? $request->status : null;
		$bet_type = $request->bet_type ? $request->bet_type : null;
		$query = ScheduleBet::whereRaw('1');
		if( $bet_type ){
			$query->where('bet_type', $bet_type);
		}
		if( $status){
			if( $status == 1 || $status == 2){
				$query->where('schedule_bet.status', $status);	
			}else{
				$query->where('schedule_bet.status','>', 2);	
			}
		}
		$dataArr = $query->whereRaw("DATE(schedule_bet.created_at) = '$ondate'")->where(['schedule_bet.provider' => self::$provider, 'schedule_bet.account_id' => self::$account_id])		
		->select('schedule_bet.*')		
		->get();
		$str_match_id = '';
		foreach( $dataArr as $data){
			$match_id = $data->match_id;
			$str_match_id .= $match_id.",";
			$tmp  = Match::where('ref_id', $match_id)->where('user_id', self::$account_id)->first();
			if($tmp){
				$detailMatch[$match_id] = $tmp->toArray();
			} 
		}
		$account_id = self::$account_id;
		//echo "<pre>";
		//var_dump($detailMatch);die;
		return view('back.report.schedule', compact('dataArr', 'ondate', 'status', 'bet_type', 'detailMatch', 'account_id'));
	}	
}
