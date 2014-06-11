<?php
/**
 * WatcherController.php
 *
 * API REST pour géré l'application mobile Water Watecher
 *
 * @author: Tibor Katelbach <tibor@pixelhumain.com>
 * Date: 14/03/2014
 */
class ApiController extends Controller {

    const moduleTitle = "Water Watcher App";
    public static $moduleKey = "waterwatcher";
    public $percent = 80; //TODO link it to unit test

    protected function beforeAction($action)
    {
        array_push($this->sidebar1, array('label' => "All Modules", "key"=>"modules", "menuOnly"=>true,"children"=>PH::buildMenuChildren("applications") ));
        return parent::beforeAction($action);
    }

    /**
     * List all the latest observations
     * @return [json Map] list
     */
	public function actionIndex() 
	{
	    $this->render("index");
	}

	
	//********************************************************************************
	//			USERS
	//********************************************************************************
	

	/**
	 * actionLogin 
	 * Login to open a session
	 * uses the generic Citoyens login system 
	 * check if application is registered on user account 
	 * @return [type] [description]
	 */
	public function actionLogin() 
	{
		$email = $_POST["email"];
		$res = Citoyen::login( $email , $_POST["pwd"]);	
		$res = array_merge($res, Citoyen::applicationRegistered($this::$moduleKey,$email));
		
		Rest::json($res);
	    Yii::app()->end();
	}
	/**
	 * [actionAddWatcher 
	 * create or update a user account
	 * if the email doesn't exist creates a new citizens with corresponding data 
	 * else simply adds the watcher app the users profile ]
	 * @return [json] 
	 */
	public function actionSaveUser() 
	{
		$email = $_POST["email"];

		//if exists login else create the new user
		echo Citoyen::register( $email, $_POST["pwd"]);
		if(Yii::app()->mongodb->citoyens->findOne( array( "email" => $email ) )){
			//udate the new app specific fields
			$newInfos = array();
			if( isset($_POST['cp']) )
				$newInfos['cp'] = $_POST['cp'];
			if( isset($_POST['name']) )
				$newInfos['name'] = $_POST['name'];
			if( isset($_POST['phoneNumber']) )
				$newInfos['phoneNumber'] = $_POST['phoneNumber'];

			$newInfos['applications'] = array( $this::$moduleKey => array( "usertype" => $_POST['type']  ));
			//$newInfos['lang'] = $_POST['lang'];
			
			Yii::app()->mongodb->citoyens->update( array("email" => $email), 
                                                   array('$set' => $newInfos ) 
                                                  );
		}
	    Yii::app()->end();
	}
	/**
	 * [actionGetWatcher get the user data based on his id]
	 * @param  [string] $email   email connected to the citizen account
	 * @return [type] [description]
	 */
	public function actionGetUser($email) 
	{
		$res = true;
		$user = Yii::app()->mongodb->citoyens->findOne( array( "email" => $email ) );
	    echo json_encode( $user );
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
	public function actionObservations( $type ) 
	{
	    echo json_encode( iterator_to_array( Yii::app()->mongodb->observations->find( array( "type" => $type ) ) ) ) ;
	    Yii::app()->end();
	}

	/**
	 * Add a new observation to the the collection "observations"
	 * @return [json] result, id(newly created)
	 */
	public function actionAddObservation() 
	{
	   if( isset( Yii::app()->session["userId"] ) && Yii::app()->request->isAjaxRequest  && isset( $_POST["who"] ) )
		{
			//TODO : validate POST data to an observation model 
			$newObservation = array(
                "type" => $_POST["type"] , 
		    	"who" => $_POST["who"] , //'who' => Yii::app()->session["userId"],
	    	    "when" => $_POST["when"] , 
	    	    "where" => $_POST["where"],
	    	    "what" => $_POST["what"],
	    	    "description" => $_POST["description"]
            );
              
            Yii::app()->mongodb->observations->insert( $newObservation );
	    	echo json_encode( array( "result"=>true,  
	    							 "id"=>$newObservation["_id"] ) );
		} else 
			echo json_encode(array("result"=>false, "msg"=>"Something went wrong. Maybe you not loggued in."));
	    Yii::app()->end();
	}
	/**
	 * return a given observation based on given id 
	 * @param  [type] $id corresponds to a given observation id
	 * @return [json] detail of an observation 
	 */
	public function actionGetObservation( $id ) 
	{
	    echo json_encode( Yii::app()->mongodb->observations->findOne( array( "_id" => new MongoId($id) ) ) )  ;
	    Yii::app()->end();
	}
	/**
	 * retreive all observations for a given citizen id
	 * @param  [type] $id a given citezens unique mongo identifier
	 * @return [json]     
	 */
	public function actionMyObservation($id) 
	{
	    echo json_encode( iterator_to_array( Yii::app()->mongodb->observations->find( array( "who" => $id ) ) ) ) ;
	    Yii::app()->end();
	}
	/**
	 * Preapres all the lists needed for the water watcher context
	 * @return [json]      [description]
	 */
	public function actionGetObservationForm() 
	{
		$form = array();
		
		$where = Yii::app()->mongodb->lists->findOne( array( "name" => "surfSpotReunion" ),array("list") ) ;
		$form["where"] = $where["list"];
		
		$what = Yii::app()->mongodb->lists->findOne( array( "name" => "typeObservationReunion" ),array("list") ) ;
		$form["what"] = $what["list"];

		$sharkObservationReunion = Yii::app()->mongodb->lists->findOne( array( "name" => "sharkObservationReunion" ),array("list","label") ) ;
		$form["sharkObservationReunion"] = $sharkObservationReunion;

		$visibilityObservationReunion = Yii::app()->mongodb->lists->findOne( array( "name" => "visibilityObservationReunion" ),array("list","label") ) ;
		$form["visibilityObservationReunion"] = $visibilityObservationReunion;

		$polutionObservationReunion = Yii::app()->mongodb->lists->findOne( array( "name" => "polutionObservationReunion" ),array("list","label") ) ;
		$form["polutionObservationReunion"] = $polutionObservationReunion;

		$sanitaryRiskObservationReunion = Yii::app()->mongodb->lists->findOne( array( "name" => "sanitaryRiskObservationReunion" ),array("list","label") ) ;
		$form["sanitaryRiskObservationReunion"] = $sanitaryRiskObservationReunion;

		$surferCountObservationReunion = Yii::app()->mongodb->lists->findOne( array( "name" => "surferCountObservationReunion" ),array("list","label") ) ;
		$form["surferCountObservationReunion"] = $surferCountObservationReunion;

		$vigilanceObservationReunion = Yii::app()->mongodb->lists->findOne( array( "name" => "vigilanceObservationReunion" ),array("list","label") ) ;
		$form["vigilanceObservationReunion"] = $vigilanceObservationReunion;
	 	
	 	echo json_encode( $form ) ;
	    Yii::app()->end();   
	}
	//********************************************************************************
	//			TOOLS
	//********************************************************************************
	
	public function actionGetClosestLocation($type,$lat,$lon)
	{
		echo "TODO";
	}
}