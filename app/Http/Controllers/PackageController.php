<?php

namespace App\Http\Controllers;

//Models
use App\Http\Models\Package;
use App\Http\Models\PackageOption;
use App\Http\Models\GroupOS;

class PackageController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function Get($cpu,$ram,$disk,$day)
    {
        $PackageOption=new PackageOption();
        $modelPk=$PackageOption->get($cpu,$ram,$disk,$day);
        if (!isset($modelPk['pko_id'])){
            return $this->responseError('Package cannot found');
        }
        return $this->responseSuccess($modelPk);
    }

    public function List()
    {
        $Package=new Package();
        $modelPk=$Package->getConfig();
        if (!$modelPk||$modelPk==null){
            return $this->responseError('Cannot get config from package');
        }
        return $this->responseSuccess($modelPk);
    }

    public function ListDetailOS()
    {
        $GroupOS = new GroupOS();
        $listGOS = $GroupOS->list();
        if (!$listGOS) {
            return $this->responseError('Cannot get list detail OS');
        }

        try {
            $paramResponse = array();
            foreach ($listGOS as $key => $value) {
                $check = TRUE;
                foreach ($paramResponse as $valueTemp) {
                    //tìm xem $value đả có trong $paramResponse chưa, nếu đả có không thêm nữa
                    if ($valueTemp['id'] == $value['gos_id']) {
                        $check = FALSE;
                        break;//kết thúc vòng for
                    }
                }
                if ($check) {//nếu chưa có thêm vào $paramResponse
                    $paramResponse[] = array(
                        'id' => $value['gos_id'],
                        'name' => $value['gos_name'],
                        'img' => $value['gos_image'],
                    );
                }
            }

            //add danh sách image vào
            foreach ($paramResponse as $key => $value) {
                foreach ($listGOS as $valueOS) {
                    if ($value['id'] == $valueOS['gos_id']) {
                        $paramResponse[$key]['listImage'][] = array(
                            'id' => $valueOS['im_code'],
                            'name' => $valueOS['im_name'],
                        );
                    }
                }
            }
        } catch (Exception $ex) {
            return $this->responseError('Cannot get list detail OS');
        }


        return $this->responseSuccess($paramResponse);
    }

}
