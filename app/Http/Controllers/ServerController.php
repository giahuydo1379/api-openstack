<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Models\PackageOption;
use App\Http\Models\Images;
use App\Http\Models\Vms;
use App\Http\Models\Users;
use App\Http\Models\Network;
use App\Http\Models\Location;
use App\Http\Models\Package;
use App\Http\Models\IpVms;
use App\Http\Models\MailQueue;
use PHPUnit\Runner\Exception;
use Validator;

class ServerController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public $headers = array();

    public function __construct()
    {
        //$this->middleware('check.authorize');
    }

    public function Create(Request $request)
    {

        //4 tham số để create server gồm: packageOpId, imageId ,serverName, userId
        $validator = Validator::make($request->all(),
            [
                'userId' => 'required|integer|bail|digits_between:1,50',
                'imageId' => 'required|string|alpha_dash|max:100|bail',
                'serverName' => "required|string|max:100|regex:/^[a-zA-Z0-9, \-'.\p{L}]+$/ui",
                'packageOpId'=> 'required|integer|bail|digits_between:1,10',
            ]

        );
        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->responseError($errors);
        }

        $PackageOption = new PackageOption();
        //example packageOpId : 17 486
        $infoPackageOp = $PackageOption->getDetailPackage($request->get('packageOpId'), true, true);
//        $infoPackageOp = $PackageOption->getDetailPackage(486);
//        return $this->responseSuccess($infoPackageOp);

        if ($infoPackageOp["pko_id"] == null) {
            return $this->responseError('The packageOpId is invalid');
//            return $this->responseError('Cannot found the snapshot packaged');
        }
//        return $this->responseError($infoPackageOp);

        try {
            // get the first records
            //example imageId: '66ffc39b-d2fb-41c0-a7e3-8e8261d016a5'
            $infoImage = Images::find($request->get('imageId'));
            if ($infoImage["im_code"] == null || $infoImage["im_name"] == null) {
                return $this->responseError('The imageId is invalid');
            }

            $userModel = Users::find($request->get('userId'));
            if ($userModel["user_id"] == null) {
                return $this->responseError('The userId is invalid');
            }
        } catch (Exception $ex) {
            return $this->responseError('Error in checking the database');
        }

//        if (!$this->check_ConfigMinimum($infoPackageOp, $infoImage)) {
//            //data response back
//            return $this->responseError('The package config is invalid');
//        }

        //get the net code
//        $net_code = 'cc60d591-3d48-4299-a856-c7e8f54245e8';
        $net_code = $this->getIdNetwork();
        if ($net_code == null) {
            return $this->responseError('The network is out of ip', 400);
        }

        if(!$this->GetAccessToKenOpenStackUser($userModel))
        {
            return $this->responseError('Get Access Token from Openstack Fail');
        }

        if(empty($userModel['user_tenant_id'])){
            return $this->responseError('Tenant id is null');
        }


        $modelVM = new Vms();
        $modelVM->vm_code = 'gen' . $this->generateString(16); // random string
        $modelVM->vm_name = $userModel["user_id"] . $this->generateString(16);     // random string
        $modelVM->vm_name_show = $request->get('serverName');
        $modelVM->vm_status = "don't vm";                          //
        $modelVM->vm_delete = 0;
//        $modelVM->vm_active = 1;
        $modelVM->vm_active = 0;
        $modelVM->vm_location_id = 1;
        $modelVM->vm_package_id = $infoPackageOp['pk_id'];
        $modelVM->vm_pass = 'gen';///////
        $modelVM->vm_user_id = $userModel["user_id"];
        $modelVM->vm_create_date = date('Y-m-d H:i:s');
        $modelVM->vm_update_date = date('Y-m-d H:i:s');
        $modelVM->vm_os_code = $infoImage['im_code'];
        $modelVM->vm_os_name = $infoImage['im_name'];
        $modelVM->vm_update_status_date = date('Y-m-d H:i:s');
        $modelVM->vm_net_code = $net_code;
        $modelVM->vm_expires_in = date('Y-m-d', strtotime('+' . $infoPackageOp['pko_day'] . ' days'));
        //save vm info to database first time
//        if (!$modelVM->save()) {
//            $this->responseError('Create VM in database fail');
//        }

        //send request to create VM in Openstack
        $resultCreateVM = $this->callApiOpenstackCreateVM(
            $modelVM->vm_name,
            $userModel['user_tenant_id'],
            $infoImage['im_code'],
//            '8e120832-052d-43b7-afcc-31c39b703522',
            $infoPackageOp['flavor_code'],
            $net_code,
            $userModel['user_private_network_id']
        );

//        return $this->responseError($resultCreateVM);
        if (!$resultCreateVM['state']) {
            return $this->responseError($resultCreateVM);
//            return $this->responseError('Create VM in Openstack fail');
        }

        $modelVM->vm_code = $resultCreateVM['param']['server']['id'];
        $modelVM->vm_status = "build";
        $modelVM->vm_pass = $resultCreateVM['param']['server']['adminPass'];

        //try to update the vm_default_qos
        $paramQos = array(
            //    'vm_id' => $result['param']['server']['id'],
            'vm_id' => $resultCreateVM['param']['server']['id'],
            'bandwidth' => env("OPENSTACK_bandwidthIp0"),
            'tenant_id' => $userModel['user_tenant_id'],
        );
        $uri = env("OPENSTACK_QOS") . '/default';
        $resultCallApiQos = $this->sendRequest($uri, $paramQos, "POST", $this->headers);
        if ($resultCallApiQos['state'] == true) {
            //update lại trường vm_default_qos
            $modelVM->vm_default_qos = 1;
        }

        //save vm info to database
        if (!$modelVM->save()) {
            $this->responseError('Save data VM to database fail');
        }

//        //send mail to user
//        if (isset($userModel['user_email'])){
//            $infoMail = array(
//                'userName' => $userModel['user_last_name'] . " " . $userModel['user_first_name'],
//                'packageName' => $infoPackageOp['pk_name'],
////                'packageName' => 'fdrive snapshot',
////                'publicIp' => 'fdrive snapshot',
////                'privateIp' => 'fdrive snapshot',
//                'userOs' => (strpos($modelVM->vm_os_name, 'Windows') !== FALSE) ? 'admin' : 'root',//
//                'password' => "'" . $modelVM->vm_pass . "'",
//            );
//            $SaveMail=$this->saveMail($userModel['user_email'],$infoMail,1);
//            if (!$SaveMail) {
//                $this->responseError('Save mail to database fail');
//            }
//        }


        //return $this->responseSuccess('Server ' . $modelVM->vm_name_show . ' have been created');
        return response()->json([
            'state' => true,
            'param' => 'Server ' . $modelVM->vm_name_show . ' have been created',
            'createVM' => $resultCreateVM,
            'Qos' => $resultCallApiQos
        ])
            ->header('Content-Type', 'application/json');
    }

    public function List($userId)
    {
        $userModel = Users::find($userId);
        if ($userModel["user_id"] == null) {
            return $this->responseError('The userId is invalid');
        }
        $listResponse = array();
        $Vms = new Vms();
        try {
            $listVM = $Vms->list($userModel["user_id"]);

            foreach ($listVM as $i => $value) {
                $vm_create_date = date("Y-m-d h:i:s", strtotime($value['vm_create_date']));
                $vm_expires_in = date("Y-m-d h:i:s", strtotime($value['vm_expires_in']));
                //get link image
                $Images = new Images();
                $modelDetailImage = $Images->getImageNoStatus($value['vm_os_code']);
                //return $modelDetailImage->gos_image;
                $gos_image = ($modelDetailImage) ?  $modelDetailImage->gos_image : '';


                $listResponse[$i]['ID'] = $value['vm_code'];
                $listResponse[$i]['OS'] = $value['vm_os_name'];
                $listResponse[$i]['Link_ImageOs'] = $gos_image;
                $listResponse[$i]['Name'] = $value['vm_name_show'];
                $listResponse[$i]['Status'] = ($value['vm_delete']) ? 'deleting' : $value['vm_status'];
                $listResponse[$i]['Create_Date'] = $vm_create_date;
                $listResponse[$i]['Expires_In'] = $vm_expires_in;
                $listResponse[$i]['Update_Status_Date'] = $value['vm_update_status_date'];

                $Location_Name = Location::find($value['vm_location_id']);
                $listResponse[$i]['Location'] = $Location_Name['location_name_show'];
                $listResponse[$i]['Package_Id'] = $value['vm_package_id'];
                $listResponse[$i]['Ram'] = $value['flavor_ram'];
                $listResponse[$i]['Cpu'] = $value['flavor_cpu'];
                $listResponse[$i]['Disk'] = $value['flavor_disk'];

                $modelPackage = Package::find($value['vm_package_id']);
                $listResponse[$i]['Package_Name'] = $modelPackage['pk_name'];

                //danh sách ip của vm
                $IpVms = new IpVms();
                $IpVMs = $IpVms->findbyVmcode($value['vm_code']);
                $arrIP_public = $arrIP_private = array();
                foreach ($IpVMs as $ip) {
                    $attrIP = ($ip['ipvm_public_private'] == 1) ? 'arrIP_private' : 'arrIP_public';
                    array_push($$attrIP, $ip['ipvm_ip']);
                }
                $listResponse[$i]['ListIP']['ip_public'] = $arrIP_public;
                $listResponse[$i]['ListIP']['ip_private'] = $arrIP_private;
            }

            return $this->responseSuccess($listResponse);
        } catch (Exception $ex) {
            return $this->responseError('Get list server fail');
        }
    }

    public function CheckStatus($vmId, $userId)
    {
        $Vms = Vms::find($vmId);
        if (!isset($Vms)) {
            return $this->responseError('Vm id is not found');
        }
        $userModel = Users::find($userId);
        if (!isset($userModel)) {
            return $this->responseError('User id is not found');
        }


        if (($userModel['user_tenant_id'] == NULL) || !$userModel['user_tenant_id']) {
            return $this->responseError('Tenant Id is not found');
        }

        //get Access Token from Openstack
        if(!$this->GetAccessToKenOpenStackUser($userModel))
        {
            return $this->responseError('Get Access Token from Openstack Fail');
        }

        $uri = env("OPENSTACK_ComputeV2") . $userModel['user_tenant_id']
            . '/servers/' . $Vms['vm_code'];

        $result = $this->sendRequest($uri, NULL, "GET", $this->headers);
//        return $this->responseError($result);

        if (!$result['state']) {
            $Vms->vm_status = 'not found';
            $Vms->vm_update_status_date = date('Y-m-d H:i:s');
            $Vms->save();
            return $this->responseError('Forbidden user');
        }

        //xong tác vụ thì câp nhật lại status db
        if ($result['param']['server']['OS-EXT-STS:task_state'] != NULL) {
            if($result['param']['server']['status']=='ERROR'){
                $Vms->vm_status = 'error';
            }
            else{
                $Vms->vm_status = 'build';
            }
            $Vms->vm_update_status_date = date('Y-m-d H:i:s');
            $Vms->save();
            return $this->responseError('Server running tasks');
        }

        if ($Vms->vm_status != strtolower($result['param']['server']['status'])) {
            //nếu trạng thái là verify_resize thì phải đồng nghĩa với chưa xong
            if (strtolower($result['param']['server']['status']) == 'verify_resize') {
                $Vms->vm_status = 'resize';
                $Vms->vm_update_status_date = date('Y-m-d H:i:s');
                $Vms->save();
                return $this->responseError('Server running tasks');
            } else {
                $Vms->vm_status = strtolower($result['param']['server']['status']);
                $Vms->vm_update_status_date = date('Y-m-d H:i:s');
                $Vms->save();
                $responseParam = array('status' => strtolower($result['param']['server']['status']));
                return $this->responseSuccess($responseParam);
            }
        } else {
            foreach ($result['param']['server']['addresses'] as $arrAddress) {
                foreach ($arrAddress as $key => $infoIp) {
                    //thêm mới nếu không tìm thấy
                    $Ipvms=new Ipvms();
                    $modelIpvmsFind = $Ipvms->findbyVmcodeIp($vmId,$infoIp['addr']);

                    if ($this->isPrivateIp($infoIp['addr'])) {
                        $ipvm_public_private = 1;
                        $ipvm_bandwidth = NULL;
                    } else {
                        $ipvm_public_private = 0;
                        $ipvm_bandwidth = 100;
                    }
                    if (!isset($modelIpvmsFind['ipvm_ip'])) {
                        $modelIpvms = new Ipvms();
                        $modelIpvms->ipvm_create_date = date('Y-m-d H:i:s');
                        $modelIpvms->ipvm_vm_code = $result['param']['server']['id'];
                        $modelIpvms->ipvm_ip = $infoIp['addr'];
                        $modelIpvms->ipvm_type = ($key == 0) ? 0 : 1;
                        $modelIpvms->ipvm_public_private = $ipvm_public_private;
                        $modelIpvms->ipvm_bandwidth = $ipvm_bandwidth;
                        $modelIpvms->ipvm_checkQos = $Vms['vm_default_qos'];
                        $modelIpvms->save();
                    }
                }
            }

            $responseParam = array('status' => strtolower($result['param']['server']['status']));
            return $this->responseSuccess($responseParam);
        }
    }

    public function TestStatus($vmId, $userId)
    {
        $Vms = Vms::find($vmId);
        if (!isset($Vms)) {
            return $this->responseError('Vm id is not found');
        }
        $userModel = Users::find($userId);
        if (!isset($userModel)) {
            return $this->responseError('User id is not found');
        }


        if (($userModel['user_tenant_id'] == NULL) || !$userModel['user_tenant_id']) {
            return $this->responseError('Tenant Id is not found');
        }

        //get Access Token from Openstack
        if(!$this->GetAccessToKenOpenStackUser($userModel))
        {
            return $this->responseError('Get Access Token from Openstack Fail');
        }

        $uri = env("OPENSTACK_ComputeV2") . $userModel['user_tenant_id']
            . '/servers/' . $Vms['vm_code'];

        $result = $this->sendRequest($uri, NULL, "GET", $this->headers);
        return $this->resSuccess($result);
    }

    public function ScheduleCheckStatus()
    {
        $Vms = Vms::whereNotIn('vm_status', ['not found','error'])
//        $Vms = Vms::where('vm_active','=','0')
            ->select(['vm_code','vm_name','vm_os_name','vm_package_id','vm_user_id','vm_status','vm_name_show'])
            ->get();
        if($Vms->isEmpty()){
            return $this->responseError('No vm build');
        }

        try {
            foreach ($Vms as $value) {
                $this->CheckStatus($value['vm_code'], $value['vm_user_id']);
                $VmsCheck = Vms::find($value['vm_code']);
//                return $this->responseError($VmsCheck['vm_status']);
                if ($VmsCheck['vm_status'] == 'active' && ($VmsCheck['vm_active']==0)) {
                    $userModel = Users::find($value['vm_user_id']);
//                    $detailVms = json_decode($this->Detail($value['vm_code'], $value['vm_user_id']));
//                    if (!$detailVms['state']) {
//                        break;
//                    }
                    $Ipvms = new Ipvms();
                    $modelIpvms = $Ipvms->findbyVmcode($value['vm_code']);
                    foreach ($modelIpvms as $aIpvms) {
                        if ($aIpvms['ipvm_public_private']==1) {
                            $privateIp = $aIpvms['ipvm_ip'];
                        } else {
                            $publicIp = $aIpvms['ipvm_ip'];
                        }
                    }
                    $package=Package::find($VmsCheck['vm_package_id']);
//                    return $this->responseSuccess($detailVms);
                    if (isset($userModel['user_email'])) {
                        $infoMail = array(
                            'userName' => $userModel['user_last_name'] . " " . $userModel['user_first_name'],
                            'packageName' => $package['pk_name'].' ('.$package['pk_description'].')',
//                            'userOs' => (strpos($detailVms['param']['image']['name'], 'Windows') !== FALSE) ? 'admin' : 'root',
                            'userOs' => 'root',
                            'password' => $VmsCheck['vm_pass'],
                            'publicIp'=> $publicIp,
                            'privateIp'=> $privateIp,
                            'serverName'=>$VmsCheck['vm_name_show']
                        );
                        $this->saveMail($userModel['user_email'], $infoMail, 1);
                    }
                    $VmsCheck->vm_active=1;
                    $VmsCheck->save();
                }
            }
        }
        catch (Exception $ex){
            return $this->responseError('Error exception');
        }
        return $this->responseSuccess('Finished Schedule Check Status');
    }

    //this function use for check the status of every vm is avaiable in current time
    public function TestCheckAllStatus()
    {
        $Vms = Vms::select(['vm_code','vm_name','vm_os_name','vm_package_id','vm_user_id','vm_status'])
            ->get();
        if($Vms->isEmpty()){
            return $this->responseError('No vm');
        }

        try {
            foreach ($Vms as $value) {
                $statusVmRequest = $this->CheckStatus($value['vm_code'], $value['vm_user_id']);
                $statusVm=json_decode($statusVmRequest);
//                return $this->responseError($statusVm);
                if ($statusVm['param']['status'] == 'active') {
                    $userModel = Users::find($value['vm_user_id']);
                    $detailVms = $this->Detail($value['vm_code']);
                    if (!$detailVms['state']) {
                        break;
                    }
                    if (!isEmpty($userModel['user_email'])) {
                        $infoMail = array(
                            'userName' => $userModel['user_last_name'] . " " . $userModel['user_first_name'],
                            'packageName' => $detailVms['param']['package']['name'],
                            'userOs' => (strpos($detailVms['param']['image']['name'], 'Windows') !== FALSE) ? 'admin' : 'root',//
                            'password' => $detailVms['param']['pass_login'],
                        );
                        $this->saveMail($userModel['user_email'], $infoMail, 1);
                    }
                }
            }
        }
        catch (Exception $ex){
            return $this->responseError('Error exception');
        }
        return $this->responseSuccess('Finished');
    }

    public function Action($action, $vmId, $userId)
    {
        //This function use to take control the virtual Machine for these action: boot, reset, shutdown.
        $modelVM = Vms::find($vmId);
        if (!isset($modelVM)) {
            return $this->responseError('Vm id is not found');
        }
        switch ($action) {
            case 'boot':
                $param = array(
                    "os-start" => null
                );
                $vm_status = 'Active';
                break;
            case 'reset':
                $param = array(
                    "reboot" => array(
                        "type" => "SOFT"
                    )
                );
                $vm_status = 'Reboot';
                break;
            case 'shutdown':
                $param = array(
                    "os-stop" => null
                );
                $vm_status = 'Shutoff';
                break;
            default:
                return $this->responseError  ('Action is not valid');
        }

        $userModel = Users::find($userId);
        if (!isset($userModel)) {
            return $this->responseError('User id is not found');
        }
        if ($userModel['user_id']!=$modelVM['vm_user_id']) {
            return $this->responseError('This user cannot access.');
        }
        if (($userModel['user_tenant_id'] == NULL) || !$userModel['user_tenant_id']) {
            return $this->responseError('Tenant Id is not found');
        }

        if ($modelVM['vm_delete'] == 1 || $modelVM['vm_delete'] == 2) {
            return $this->responseError('Server have been deleted');
        }
        if ($modelVM['vm_active'] == 0) {
            return $this->responseError('Server have been locked');
        }
        if ($modelVM['vm_status'] == "don't vm") {
            return $this->responseError('Server have been crashed');
        }
        if ($modelVM['vm_task_status'] == 'build') {
            return $this->responseError('Server is recovering to backup now, cannot access');
        }
        if (date("Y-m-d h:i:s", strtotime($modelVM['vm_expires_in'])) < date('Y-m-d H:i:s')) {
            return $this->responseError('Server have been expired');
        }

        //get Access Token from Openstack
        if(!$this->GetAccessToKenOpenStackUser($userModel))
        {
            return $this->responseError('Get Access Token from Openstack Fail');
        }


        $uri = env("OPENSTACK_ComputeV2") . $userModel['user_tenant_id']
            . '/servers/' . $modelVM['vm_code'] . '/action';
        $result = $this->sendRequest($uri, $param, "POST", $this->headers);
        if (!$result['state']) {
            if (isset($result['error']['itemNotFound']['code']) &&
                strtolower($result['error']['itemNotFound']['code']) == 404) {
                return $this->responseError('Cannot find the server');
            }
            return $result;
        }

        $modelVM->vm_status = strtolower($vm_status);
        $modelVM->save();

        return $this->responseSuccess($result);
    }


    public function GetAccessToKenOpenStack($TenantId = NULL)
    {

        if ($TenantId == NULL) {
            $TenantId = env("OPENSTACK_TenantId_Default");//lấy project mặc định (admin)
        }

        $params = array(
            "auth" => array(
                "tenantId" => $TenantId,
                'passwordCredentials' => array(
                    "username" => env("OPENSTACK_UserName"),
                    "password" => env("OPENSTACK_PassWord"),
                )
            )
        );

        $uri = env("OPENSTACK_Identity") . 'tokens';
        $headers[] = 'Content-Type: application/json';
//        var_dump($uri);
//        echo json_encode($params);
        $result = $this->sendRequest($uri, $params, "POST", $headers);

        $responseState = array('state' => FALSE);
        if ($result['state']) {
            $responseState['state'] = true;
            $responseState['param'] = $result['param']['access'];
        } else {
            $responseState['error'] = $result['error'];
        }
        return $responseState;
    }

    public function callApiOpenstackCreateVM($vm_name, $user_tenant_id, $imageRef, $flavorRef, $private_public_id, $private_network_id)
    {
        //gọi api openstack để tạo vm và cập nhật lại dữ liệu trong db
        $param = array(
            "server" => array(
                "name" => $vm_name,
                "imageRef" => $imageRef,
                "flavorRef" => $flavorRef,
//                "flavorRef" => '8e120832-052d-43b7-afcc-31c39b703522',
                "availability_zone"=> "Nova:compute09",
                "max_count" => 1,
                "min_count" => 1,
                "networks" => array(
                    0 => array(
                        "uuid" => $private_network_id,
                    ),
                    1 => array(
                        "uuid" => $private_public_id,
                    )
                ),
                "security_groups" => array(
                    0 => array(
                        "name" => "default"
                    )
                )
            )
//              ,
//            "block_device_mapping_v2"=>array(
//                "uuid"=> "8e120832-052d-43b7-afcc-31c39b703522",
//                "source_type" => "snapshot"
//            )
//            ,
//            "OS-SCH-HNT:scheduler_hints"=>array(
//                "build_near_host_ip"=> "10.200.0.59"
//            )
        );

        $uri = env("OPENSTACK_ComputeV2") . $user_tenant_id . '/servers';
        return $this->sendRequest($uri, $param, "POST", $this->headers);
    }


    public function check_ConfigMinimum($infoPackageOp, $infoImage)
    {

        //kiểm tra cấu hình tối thiểu hệ điều hành ng dùng chọn và gói package có phù hợp hay không
        //nếu các thông số trong $infoImage là 0 thì là ko có limit

        if ($infoImage['im_min_disk'] != 0
            && $infoImage['im_min_disk'] > $infoPackageOp['flavor_disk']) {
            return false;
        }
        if ($infoImage['im_min_ram'] != 0
            && $infoImage['im_min_ram'] > $infoPackageOp['flavor_ram']) {
            return false;
        }
        if ($infoImage['im_min_cpu'] != 0
            && $infoImage['im_min_cpu'] > $infoPackageOp['flavor_cpu']) {
            return false;
        }
        return true;
    }

    public function getIdNetwork()
    {
        //This function return the network code in cloud_network table
        //thuật tuán là duyệt qua danh sách list network
        //nếu số lượng ip trên net_code đó nhỏ hơn limit_network/2 thì lấy
        //nếu all các net_code đều đủ ip (số lượng ip lơn hơp limit_network/2)
        //thì lấy net_code nào có số lượng Ip thấp nhất
        //tat ca net_code dau co so luong ip > limit ip thi tra ve null
        $Network = new Network();
        $listNetwork = $Network->getListNetwork();
        $min_net_code = null;
        $min_count_ip = null;
        foreach ($listNetwork as $key => $value) {
            if ($value['net_count_ip'] < $value['net_limit_ip'] / 2)
                return $value['net_code'];
            if ($value['net_count_ip'] <= $value['net_limit_ip']) {
                if ($min_count_ip == null) {
                    $min_count_ip = $value['net_count_ip'];
                    $min_net_code = $value['net_code'];
                } else {
                    if ($min_count_ip > $value['net_count_ip']) {
                        $min_count_ip = $value['net_count_ip'];
                        $min_net_code = $value['net_code'];
                    }
                }

            }
        }

        return $min_net_code;

    }

    public function GetAccessToKenOpenStackUser(&$userModel)
    {

        if (($userModel['user_tenant_id'] == NULL) || !isset($userModel['user_tenant_id'])) {
            $result = $this->GetAccessToKenOpenStack();
        }
        else{
            $result = $this->GetAccessToKenOpenStack($userModel['user_tenant_id']);
        }


        if (!$result['state']) {
            return false;
        }

        $this->headers = array(
            'X-Auth-Token: ' . $result['param']['token']['id'],
            'Content-Type: application/json'
        );

        //user_tenant_id is ok, return true
        if (($userModel['user_tenant_id'] != NULL) && isset($userModel['user_tenant_id'])) {
            return true;
        }

        //get the new tenant id
        $uri = env("OPENSTACK_TENANT") . $userModel["user_id"];
        $result = $this->sendRequest($uri, NULL, "POST", $this->headers);
//        return $result;
        if ($result['state']) {
            //cập nhật lại db
            $userModel['user_tenant_id'] = $result['param']['info']['tenant_id'];
            $userModel['user_private_network_id'] = $result['param']['info']['network_id'];
            $userModel['user_private_subnet_id'] = $result['param']['info']['subnet_id'];
            if ($userModel->update()) {
                //cập nhật lại accessToken
                $result = $this->GetAccessToKenOpenStack($userModel['user_tenant_id']);
                $this->headers = array(
                    'X-Auth-Token: ' . $result['param']['token']['id'],
                    'Content-Type: application/json'
                );
                return true;

            } else {
                return false;
            }
        }
    }


    public function Detail($vmId)
    {
        try {
            $modelVM = Vms::find($vmId);

            if (!isset($modelVM)) {
                return $this->responseError('Vm id is not found');
            }

            if ($modelVM['vm_delete'] == 1 || $modelVM['vm_delete'] == 2) {
                return $this->responseError('Server have been deleted');
            }
            if ($modelVM['vm_active'] == 0) {
                return $this->responseError('Server have been locked');
            }

            if (!isset($modelVM['vm_user_id'])) {
                return $this->responseError('Vm User id is not found');
            }


            $Location = Location::find($modelVM['vm_location_id']);
            $Package = new Package();
            $detailPackage = $Package->getDetailPackage($modelVM['vm_package_id']);

            $responseParam = array(
                'id' => $modelVM['vm_code'],
                'status' => $modelVM['vm_status'],
                'name' => $modelVM['vm_name_show'],
                'availability_zone' => $Location['location_name_show'],
                'created' => $modelVM['vm_create_date'],
                'updated' => $modelVM['vm_expires_in'],
                'expires_in' => $modelVM['vm_update_date'],
                'pass_login' => $modelVM['vm_pass'],
                'vm_task_snap_id' => $modelVM['vm_task_snap_id'],
                'package' => array(//flavor
                    'id' => $modelVM['vm_package_id'],
                    'name' => $detailPackage['pk_name'],
                    'disk' => $detailPackage['flavor_disk'],
                    'ram' => $detailPackage['flavor_ram'],
                    'cpu' => $detailPackage['flavor_cpu'],
                ),
                'image' => array(
                    'id' => $modelVM['vm_os_code'],
                    'name' => $modelVM['vm_os_name'],
                ),
                'ip_address' => array(
                    'public' => array(),
                    'private' => array(),
                ),
            );

            $responseParam['task'] = NULL;
            if ($modelVM['vm_task_status'] == 'build') {
                $responseParam['task'] = 'running recover';
                $responseParam['backupid_recover'] = $responseParam['vm_task_snap_id'];
            }

            $Ipvms = new Ipvms();
            $modelIpvms = $Ipvms->findbyVmcode($vmId);
            foreach ($modelIpvms as $aIpvms) {


                if ($aIpvms['ipvm_public_private'] == 1) {
                    $responseParam['ip_address']['private'][] = $aIpvms['ipvm_ip'];
                } else {
                    $responseParam['ip_address']['public'][] = $aIpvms['ipvm_ip'];
                }
            }

            return $this->responseSuccess($responseParam);

        } catch (Exception $exc) {
            return $this->responseError('Error exception', 500);
        }
    }



//this function use to test
    public function zz($userId)
    {
            $infoMail = array(
                'userName' =>'ldungrs',
                'packageName' => 1,
                'userOs' =>'root',//
                'password' => 'zzzzzz',
            );

        $MailQueue=new MailQueue();
        $MailQueue->client_email='ltdungrs@gmail.com';
        $MailQueue->param=json_encode($infoMail);
        $MailQueue->status=0;
        $MailQueue->layout='1';
        $MailQueue->created_date= date('Y-m-d H:i:s');
        if (!$MailQueue->save()) {
            $this->responseError('Save mail to database fail');
        }

    }

    //test function
    public function zzpost(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                'userId' => 'required|min:6|max:255',
                'packageOpId' => 'required|min:6|max:11',
                'imageId' => 'required|min:6',
                'serverName' => 'required|min:6|max:25',
            ]

            );
        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->responseError($errors);
        }
//        $zz=json_decode($request->all());
        return $request;
        try {
            foreach ($request as $value)
            {
                return  $value;
                $aa= (string)gettype ($key);
            }
        }
        catch(\Exception $e){
            return 'false';
        }
        return $aa;

//
//        if(is_infinite($request->get('packageOpId')))
//        {return 'zz';}

        if (is_int ($request->get('packageOpId'))){
             return 'aaaaaaaaaaaa';
        }
        return $request->get('packageOpId');
        return $request;
//        foreach ($request->toArray() as $key => $value)
//        {
//
//            if(strlen ($value)>100)
//            return 'too long';
//        }
        return $request->toArray();
        $userModel = Users::find($request->get('userId'));
//        return $this->resSuccess($request->get('userId'));
        if ($userModel["user_id"] == null) {
            return $this->responseError('The userId is invalid');
        }

        if(!$this->GetAccessToKenOpenStackUser($userModel))
        {
            return $this->responseError('Get Access Token from Openstack Fail');
        }

        //try to update the vm_default_qos
        $paramQos = array(
            //    'vm_id' => $result['param']['server']['id'],
            'vm_id' => 'd3b3cde3-eb18-4ac1-ac3b-57b30e6ef2de',
            'bandwidth' => 100,
            'tenant_id' => $userModel['user_tenant_id'],
        );
        $uri = env("OPENSTACK_QOS") . '/default';
        $resultCallApiQos = $this->sendRequest($uri, $paramQos, "POST", $this->headers);
        return $this->resSuccess($resultCallApiQos);

    }


}