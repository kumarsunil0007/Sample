<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
Use App\Repairs;
Use App\Tenancy;
Use App\LettingUnit;
Use App\Attachments;
Use App\RepairAttachment;
use Yajra\DataTables\DataTables;

class RepairsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->except('logout');
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function addReapirs(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->except('_token', 'attachment_name');
            $data['user_id'] = auth()->id();
            $data['letting_unit_id'] = $request->repair_for;
            $repairs = Repairs::create($data);

            if($request->has('attachment_name')){
                $file = $request->attachment_name;
                $filename = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $picture = 'attachment_'.time().'.'.$extension;
                $destinationPath = base_path() . '/public/assets/images/repair_images/';
                $file->move($destinationPath, $picture);
                if($extension == 'jpg' || $extension == 'jpeg'){
                    $this->correctImageOrientation($destinationPath.'/'.$picture);
                }
                $images_array = array( 'repairs_id' => $repairs->id, 'image' => $picture,'name' => 'Repair File');
                RepairAttachment::create($images_array);
            }
            $response = [];
            echo json_encode($response);exit;
        }
        $letting_units = LettingUnit::where('user_id',auth()->id())->get();
        return view('landlord.repairs.create', ['id' => $request->id,'letting_units' => $letting_units]);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function viewReapirs()
    {
        return view('landlord.repairs.index');
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function deleteRepairFiles(Request $request){
        $data = RepairAttachment::findOrFail(base64_decode($request->id));

        $destinationPath = base_path() . '/public/assets/images/repair_images/'.$data->image;
        $data->delete();
        if(file_exists($destinationPath)){
            unlink($destinationPath);
        }

        $response = [];
        $response['status'] = 'success';
        echo json_encode($response);
        exit(0);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    function correctImageOrientation($filename) {

	  	if (function_exists('exif_read_data')) {
		    $exif = exif_read_data($filename, 0, true);
		    if($exif && isset($exif['IFD0']['Orientation'])) {
		      	$orientation = $exif['IFD0']['Orientation'];
		      	if($orientation != 1){
			        $img = imagecreatefromjpeg($filename);
			        $deg = 0;
			        switch ($orientation) {
			          	case 3:
				            $deg = 180;
				            break;
			          	case 6:
				            $deg = 270;
				            break;
			          	case 8:
				            $deg = 90;
				            break;
			        }
			        if ($deg) {
			          	$img = imagerotate($img, $deg, 0);
			        }
			        imagejpeg($img, $filename, 95);
			    }
		    }
		}
	}

    /**
     * @param Request $request
     * @return mixed
     */
    public function updateRepairStatus(Request $request)
    {
        $data = Repairs::findOrFail($request->id);
        $data->update(['status'=> $request->status]);
        return redirect()->back()->with([
            'message-type' => 'success',
            'message'      => 'Repair Updated Successfully.'
        ]);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function fetchRepairNotes(Request $request)
    {
        $response = [];
        $response['data'] = Repairs::find(base64_decode($request->id));
        echo json_encode($response);
        exit(0);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getNexSubscriptionDate($date,$duration){

        $newDate = $date;
        for($i = 1;$i<=$duration;$i++){
            $d = new \DateTime($newDate);
            $month =  $d->format('m') + ( $d->format('d') > 29 ? 1 : 0);
            $month = $month > 12 ? 1 : $month;
            $year = $d->format('Y');
            $days_to_add = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $newDate = date('Y-m-d', strtotime($newDate. ' + '.$days_to_add.' days'));

        }
        return $newDate;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function deleteRepairRecords(Request $request)
    {
        $data = Repairs::find(base64_decode($request->id));
        if($data){
            $data->delete();
        }
        return \Response::json(200);
    }

}
