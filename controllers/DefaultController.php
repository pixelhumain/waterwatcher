<?php

/**
 * WatcherController.php
 *
 * API REST pour géré l'application mobile Water Watecher
 *
 * @author: Tibor Katelbach <tibor@pixelhumain.com>
 * Date: 14/03/2014
 */
class DefaultController extends Controller {

    const moduleTitle = "Water Watcher App";

    public static $moduleKey = "waterwatcher";

    /**
     * List all the latest observations
     * @return [json Map] list
     */
    public function actionIndex() {
        $this->render("index");
    }

    //********************************************************************************
    //			USERS
    //********************************************************************************

    /**
     * actionLogin 
     * Login to open a session
     * uses the generic Citoyens login system 
     * @return [type] [description]
     */
    public function actionLogin() {
        echo Citoyen::login($_POST["email"], $_POST["pwd"]);
        Yii::app()->end();
    }

    /**
     * [actionAddWatcher 
     * create or update a user account
     * if the email doesn't exist creates a new citizens with corresponding data 
     * else simply adds the watcher app the users profile ]
     * @return [json] 
     */
    public function actionSaveUser() {
        $email = $_POST["email"];

        //if exists login else create the new user
        echo Citoyen::register($email, $_POST["pwd"]);
        if (Yii::app()->mongodb->citoyens->findOne(array("email" => $email))) {
            //udate the new app specific fields
            $newInfos = array();
            if (isset($_POST['cp']))
                $newInfos['cp'] = $_POST['cp'];
            if (isset($_POST['name']))
                $newInfos['name'] = $_POST['name'];
            if (isset($_POST['phoneNumber']))
                $newInfos['phoneNumber'] = $_POST['phoneNumber'];

            //$newInfos['applications'] = array( "key"=> "waterwatcher", "usertype" => $_POST['type']  );
            //$newInfos['lang'] = $_POST['lang'];
            $newInfos['applications'] = array($this::$moduleKey => array("usertype" => $_POST['type'], "deviceid" => $_POST['deviceid']));
            Yii::app()->mongodb->citoyens->update(array("email" => $email), array('$set' => $newInfos)
            );
        }
        Yii::app()->end();
    }

    /**
     * [actionGetWatcher get the user data based on his id]
     * @param  [string] $email   email connected to the citizen account
     * @return [type] [description]
     */
    public function actionGetUser($email) {
        $res = true;
        $user = Yii::app()->mongodb->citoyens->findOne(array("email" => $email));
        echo json_encode($user);
        Yii::app()->end();
    }
    //********************************************************************************
    //			OBSERVATIONS
    //********************************************************************************

    /**
     * List all observations based on type
     * TODO : limit to 10 observations
     * @param  [type] is the observation type 
     * @return [json] list of observations
     */
    public function actionObservations($type) {
        echo json_encode(iterator_to_array(Yii::app()->mongodb->observations->find(array("type" => $type))->sort(array('when' => 1))->limit(15)));
        Yii::app()->end();
    }

    /**
     * Add a new observation to the the collection "observations"
     * @return [json] result, id(newly created)
     */
    public function actionAddObservation() {
        //Yii::app()->request->isAjaxRequest  &&
        if (isset($_POST["who"])) {
            //TODO : validate POST data to an observation model 
            $newObservation = array(
                "type" => $_POST["type"],
                "who" => $_POST["who"], //'who' => Yii::app()->session["userId"],
                "when" => $_POST["when"],
                "where" => $_POST["where"],
                "what" => $_POST["what"],
                "description" => $_POST["description"],
                "created"=>time()
            );
            if( isset($_POST['telephone']) )
                $newObservation['phoneNumber'] = $_POST['telephone'];

            Yii::app()->mongodb->observations->insert($newObservation);
            echo json_encode(array("result" => true,
                "id" => $newObservation["_id"]));
        } else
            echo json_encode(array("result" => false, "msg" => "Something went wrong."));
        Yii::app()->end();
    }

    /**
     * return a given observation based on given id 
     * @param  [type] $id corresponds to a given observation id
     * @return [json] detail of an observation 
     */
    public function actionGetObservation($id) {
        echo json_encode(Yii::app()->mongodb->observations->findOne(array("_id" => new MongoId($id))));
        Yii::app()->end();
    }

    /**
     * retreive all observations for a given citizen id
     * @param  [type] $id a given citezens unique mongo identifier
     * @return [json]     
     */
    public function actionMyObservation($id) {
        echo json_encode(iterator_to_array(Yii::app()->mongodb->observations->find(array("who" => $id))));
        Yii::app()->end();
    }
     public function actionAllObservation() {
        $res = iterator_to_array(Yii::app()->mongodb->observations->find()->limit(15) );
        //$res["count"] = count($res);
        echo json_encode($res);
        Yii::app()->end();
    }
    /**
     * Preapres all the lists needed for the water watcher context
     * @return [json]      [description]
     */
    public function actionGetObservationForm() {
        $form = array();

        $where = Yii::app()->mongodb->lists->findOne(array("name" => "surfSpotReunion"), array("list"));
        $form["where"] = $where["list"];

        $what = Yii::app()->mongodb->lists->findOne(array("name" => "typeObservationReunion"), array("list"));
        $form["what"] = $what["list"];

        $sharkObservationReunion = Yii::app()->mongodb->lists->findOne(array("name" => "sharkObservationReunion"), array("list", "label"));
        $form["sharkObservationReunion"] = $sharkObservationReunion;

        $visibilityObservationReunion = Yii::app()->mongodb->lists->findOne(array("name" => "visibilityObservationReunion"), array("list", "label"));
        $form["visibilityObservationReunion"] = $visibilityObservationReunion;

        $polutionObservationReunion = Yii::app()->mongodb->lists->findOne(array("name" => "polutionObservationReunion"), array("list", "label"));
        $form["polutionObservationReunion"] = $polutionObservationReunion;

        $sanitaryRiskObservationReunion = Yii::app()->mongodb->lists->findOne(array("name" => "sanitaryRiskObservationReunion"), array("list", "label"));
        $form["sanitaryRiskObservationReunion"] = $sanitaryRiskObservationReunion;

        $surferCountObservationReunion = Yii::app()->mongodb->lists->findOne(array("name" => "surferCountObservationReunion"), array("list", "label"));
        $form["surferCountObservationReunion"] = $surferCountObservationReunion;

        $vigilanceObservationReunion = Yii::app()->mongodb->lists->findOne(array("name" => "vigilanceObservationReunion"), array("list", "label"));
        $form["vigilanceObservationReunion"] = $vigilanceObservationReunion;

        echo json_encode($form);
        Yii::app()->end();
    }

    //********************************************************************************
    //			TOOLS
    //********************************************************************************

    public function actionGetClosestLocation($type, $lat, $lon) {
        echo "TODO";
    }
    //************************************************
         //  GSM Notification
    //***************************************************
             //generic php function to send GCM push notification
    public function actionsendPushNotificationToGCM() {
       $uid=$_REQUEST['uid'];
       $msg=$_REQUEST['msg'];
        $data = (iterator_to_array(Yii::app()->mongodb->citoyens->find()));
       
        $i = 0;
        foreach ($data as $key => $value) {
            if ($key != $uid) {
                
                $waterwatcher = $value['applications']['waterwatcher'];
                if (isset($waterwatcher)) {
                    
                    foreach ($waterwatcher as $device) {
                        if(isset($device) && $device!=""){
                        $registatoin_ids[] = $device;
                        //$custom[$i]['uid'] = $key;
                        }
                    }
                } $i++;
            }
        }
        $url = 'https://android.googleapis.com/gcm/send';
        $fields = array(
            'registration_ids' => $registatoin_ids,
            'data' => array('msg'=>$msg),
        );
       
    define("GOOGLE_API_KEY", "AIzaSyANjERlWcVQRYzuWRF2TMUN8G2-BL1w_UM");   
        $headers = array(
            'Authorization: key=' . GOOGLE_API_KEY,
            'Content-Type: application/json'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);      
        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }
        curl_close($ch);
        echo $result;
            }
 // Yii::app()->end();
}
