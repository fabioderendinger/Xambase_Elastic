<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Exception;
use App\Exceptions\ApiException;
use Validator;
use App\Document;
use App\User;
use App\Review;
use DB;

class DocumentController extends Controller
{
	


	public function getDocuments(Request $request){
		//Check if any parameter is empty
		//1 for Exam, 2 for Notes, 3 for Summeries, 4 for Homework
	//	print_r($request->all());die;
		 if(empty($request->serviceKey) || empty($request->userId) || $request->page == "" || empty($request->documentType)){
			 return response()->json([
				'errorCode' => '1',
				'errorMsg' => 'Required parameter missing.',
				'data' => ''
			]);
		 }
		 
		//Check Security Key
		 $userId = $request->userId;
		 $serviceKey = $request->serviceKey;
		 $user = User::where([
			'id' => $userId,
			'service_key' => $serviceKey
		 ])->first();
		 if(is_null($user)){
			return response()->json([
				'errorCode' => '1',
				'errorMsg' => 'You are logged in another device.',
				'data' => ''
			]);
		 }
		 
		 $documentType = $request->documentType;
		 $page = ($request->page)*$this->perPage;
		 $documents = [];
		 
		 $status = DB::transaction(function () use (&$user, $serviceKey, $documentType, $page, &$documents, $request){
		 	Document::putMapping($ignoreConflicts = true);
		 	
		 	$query = [
		 		'type' => ['document'],
		 		'body' => []
		 	];
			
			if($request->has('listType')){// listType 1 for search doc, 2 for filter doc, empty for default doc
				if($request->listType == '1'){//for search
					//Add searching criteria

					if($request->has('documentTitle')){
						$query['body']['query']['bool']['must']['multi_match'] = ['query' => $request->documentTitle, 'type' => 'most_fields', 'fields' => ["document_title^4", "document_title.ngram^2", "document_sub_title", "document_sub_title.ngram"]];
					}
					if($request->has('schoolId')){
						$query['body']['query']['bool']['should']['bool']['must'][] = ['term' => ['school_id' => $request->schoolId]];
					}

					// if($request->has('favourite')){
					// $query['body']['query']['bool']['should']['bool']['must'][] = ['term' => ['favourite' => $request->favourite]];
					//  }

					if($request->has('subjectId')){
						$query['body']['query']['bool']['should']['bool']['must'][] = ['term' => ['subject_id' => $request->subjectId]];
					}
					elseif($request->has('subjectName')){
						$query['body']['query']['bool']['should']['bool']['must'][] = ['nested' => [
								'path' => 'subject', 
								'query' => ['multi-match' => [
									'query' => $request->subjectName,
									'type' => 'most_fields',
									'fields' => ["subject_name^2", "subject_name.ngram"]
								]]
							]];
					}	
					if($request->has('teacherId')){
						$query['body']['query']['bool']['should']['bool']['must'][] = ['term' => ['teacher_id' => $request->teacherId]];
					}
					elseif($request->has('teacherName')){
						$query['body']['query']['bool']['should']['bool']['must'][] = ['nested' => [
								'path' => 'teacher', 
								'query' => ['multi-match' => [
									'query' => $request->teacherName,
									'type' => 'most_fields',
									'fields' => ["first_name^2", "first_name.ngram", "last_name^2", "last_name.ngram"]
								]]
							]];
					}
				}
				elseif($request->listType == '2'){//for filter
					if($request->has('startYear') && $request->has('endYear')){
						$query['body']['query']['bool']['filter'][] = ['range' => ['year' => ['gte' => $request->startYear, 'lte' => $request->endYear]]];
					}
					$schoolFilters = [];
					if($request->has('countryId')){
						$id = $request->countryId;
						$schoolFilters[] = ['term' => ['country_id' => $id]];
					}
					if($request->has('languageId')){
						$id = $request->languageId;
						$schoolFilters[] = ['term' => ['language_id' => $id]];
					}
					if($request->has('university')){
						$schoolFilters[] = ['term' => ['is_university' => $request->university]];
					}
					if(count($schoolFilters) > 0){
						$query['body']['query']['bool']['filter'][] = ['nested' => ['path' => 'school', 'query' => ['bool' => ['filter' => $schoolFilters]]]];
					}
				}
			}

			$query['body']['query']['bool']['filter'][] = ['term' => ['document_type' => $documentType]];
			//$query['body']['query']['bool']['filter'][] = ['term' => ['favourite' => $request->favourite]];
			$query['body']['query']['bool']['filter'][] = ['term' => ['status' => 1]];
			$query['body']['query']['bool']['filter'][] = ['term' => ['is_draft' => 0]];
			
			$query['body']['from'] = $page;
			$query['body']['size'] = $this->perPage;
			$query['body']['sort'][] = ['_score' => ['order' => 'desc']];
			$query['body']['sort'][] = ['year' => ['order' => 'desc', 'unmapped_type' => 'integer']];
		//	print_r($query);die;
			$documents = Document::complexSearch($query);
		 });
		 
		 if(is_null($status)){
		 	 $documentList = [];
		 	 $unlockedDocuments = DB::table('unlock_documents')->where('user_id', $user->id)->whereIn('document_id', $documents->pluck('id')->values()->toArray())->pluck('document_id')->values()->toArray();
//print_r($documents);die;
			 foreach($documents as $document){
				 $images = (Array)json_decode($document->images);
				 $imageList = [];
				 foreach($images as $image){
					$imageList[] = ['image' => $image];
				 }

				 $myRating = '0';
				 $ratings = '0';
				 if($document->ratings->count()){
					 $ratings = ($document->ratings->sum('rating') / $document->ratings->count());
				 }

				 $userRate = $document->ratings->where('user_id', $user->id)->first();
				 if(!is_null($userRate)){
					 $myRating = $userRate->rating;
				 }	

				 if(!is_null($document->term_id)){
 $terms = DB::table('terms')->where('id', $document->term_id)->first();
//print_r($terms->term);die;
$term_name=$terms->term;


				 }else{
			$term_name='';	 	
				 }




				 $totalReviews = $document->reviews->count();

 $useful_review = DB::table('reviews')->where('document_id', $document->id)->where('useful', '1')->get();
				 if(count($useful_review) != '0'){
$useful_review =count($useful_review);
					 
				 }else{
				 $useful_review ='0';	
				 }	

 $unuseful_review = DB::table('reviews')->where('document_id', $document->id)->where('useful', '0')->get();
				 if(count($unuseful_review) != '0'){
$unuseful_review =count($unuseful_review);
					 
				 }else{
				 $unuseful_review ='0';	
				 }	

$my_review = DB::table('reviews')->where('document_id', $document->id)->where('useful', '0')->where('user_id', $user->id)->first();
//print_r($my_review);die;
				 if($my_review != ''){
$my_review ='1';
					 
				 }else{
				 $my_review ='0';	
				 }	





				 $documentList[] = array_map(function($v){
							return (is_null($v)) ? "" : (is_array($v)) ? $v : (String)$v;
						},[
							'documentId' => $document->id,
							'document_title' => $document->document_title,
							'document_sub_title' => $document->document_sub_title,
							'course' => $document->school->is_university == '1'?$document->course->course_name:"",
							'courseId' => $document->course->id,
							'images' => $imageList,
							'school' => $document->school->school_name,
							'university' => $document->school->is_university,
							'schoolId' => $document->school->id,
							'subject' => $document->subject->subject_name,
							'subjectId' => $document->subject->id,
							'teacher' => trim($document->teacher->first_name . ' ' . $document->teacher->last_name),
							'teacherId' => $document->teacher->id,
							'year' => $document->year,
							'month' => $document->month,
							'date' => $document->date,
							'country' => $document->school->country->country_name,
							'publishedAs' => $document->published_as,
							'isDraft' => $document->is_draft,
							'favourite' => $document->favourites->where('user_id', $user->id)->count(),
							'myRating' => $myRating,
							'ratings' => $ratings,
							'totalReviews' => $totalReviews,
							'useful_review' => $useful_review,
							'unuseful_review' => $unuseful_review,
							'grade' => $document->grade,
							'weight' => $document->weight,
							'published_as' => $document->published_as,
							'term_id' => $document->term_id,
							'term_name' =>$term_name,
							'my_review'=>$my_review,
							'myDocument' => $user->id == $document->user_id?1:0,
							'unlocked' => in_array($document->id, $unlockedDocuments)?1:0
						 ]);
			 }
			


			
 $final_document='';
			 if(isset($request->favourite) == '1'){

if(isset($documentList)){

	foreach($documentList as $this_document){
	//print_r($this_document['documentId']);die;	
		$document_id=$this_document['documentId'];
		$user_id=$request->userId;
		$document_favourites = DB::table('document_favourites')
		->where('document_id',$document_id)->where('user_id',$user_id)->first();

		if($document_favourites != ''){
			$final_document[]=$this_document;

		}
		 
		}

	$documentList='';
		$documentList=$final_document;
//print_r($final_document);die;
	}

 //print_r($documentList);die;
			 }

			 return response()->json([
					'errorCode' => '0',
					'errorMsg' => 'Get Documents Successfully.',
					'serviceKey' => $serviceKey,
					'hasRecords' => count($documentList) > 0?"1":"0",
					'data' => $documentList
				]);
		 }
		 
		 return response()->json([
				'errorCode' => '1',
				'errorMsg' => 'Server error occurred. Please try again.',
				'data' => ''
			]);
	}
	
	public function addDocument(Request $request){
		$validator = Validator::make($request->all(), [
			'serviceKey' => 'required',
			'userId' => 'required',
			'deviceType' => 'required',
			'documentType' => 'required',
			'documentTitle' => 'required',
			'schoolId' => 'required',
			'subjectId' => 'required',
			'teacherId' => 'required',
			'documentDate' => 'required',
			'publishedAs' => 'required',
			'image1' => 'required'
		], [
			'serviceKey.required' => 'Required parameter missing.',
        	'userId.required' => 'Required parameter missing.',
        	'deviceType.required' => 'Required parameter missing.',
        	'documentType.required' => 'Required parameter missing.',
        	'documentTitle.required' => 'Required parameter missing.',
        	'schoolId.required' => 'Required parameter missing.',
        	'subjectId.required' => 'Required parameter missing.',
        	'teacherId.required' => 'Required parameter missing.',
        	'documentDate.required' => 'Required parameter missing.',
        	'publishedAs.required' => 'Required parameter missing.',
        	'image1.required' => 'No file was uploaded.'
		]);

		if ($validator->fails()) {
        	return response()->json([
				'errorCode' => '1',
				'errorMsg' => $validator->errors()->first(),
				'data' => ''
			]);
        }
		
		//Check Security Key
		 $userId = $request->userId;
		 $serviceKey = $request->serviceKey;
		 $user = DB::table('users')->where([
			'id' => $userId,
			'service_key' => $serviceKey
		 ])->first();
		 if(is_null($user)){
			return response()->json([
				'errorCode' => '1',
				'errorMsg' => 'You are logged in another device.',
				'data' => ''
			]);
		 }
		
		 //save multiple images
		 $imageFileName = [];
		 if(strtolower($request->deviceType) == 'android'){ // for android devices - image receives as string
			 $i = 0;
			 while(true){
				 $i++;
				 if($request->has('image'.$i)){
					 $randomKey = $this->generateServiceKey();
					 $result = $this->upload($randomKey, $request->deviceType, $request->input('image'.$i));
					 if($result['errorCode'] == 1){
						 return response()->json([
							'errorCode' => '1',
							'errorMsg' => $result['errorMsg'],
							'data' => ''
						]);
					 }
					 $imageFileName[] = $result['fileName'];
				 }
				 else{
					 break;
				 }
			 }
		 }
		 else{
			 $i = 0;
			 while(true){
				 $i++;
				 if($request->hasFile('image'.$i)){
					 $randomKey = $this->generateServiceKey();
					 $result = $this->upload($randomKey, $request->deviceType, 'image'.$i);
					 if($result['errorCode'] == 1){
						 return response()->json([
							'errorCode' => '1',
							'errorMsg' => $result['errorMsg'],
							'data' => ''
						]);
					 }
					 $imageFileName[] = $result['fileName'];
				 }
				 else{
					 break;
				 }
			 }
		 }
		 

		 $document;
		 if($request->has('documentId')){
			 $document = Document::find($request->documentId);
		 }
		 else{
			 $document = new Document;
		 }
		 $document->user_id = $userId;
		 $document->images = json_encode($imageFileName);
		 $document->document_type = $request->documentType;
		 $document->document_title = $request->documentTitle;
		 $document->document_sub_title = $request->documentSubTitle;
		 $document->school_id = $request->schoolId;
		 $document->subject_id = $request->subjectId;
		 $document->teacher_id = $request->teacherId;
		 if($request->has('courseId')){
		 	$document->course_id = $request->courseId;
		 }
		 if($request->has('termId')){
		 	$document->term_id = $request->termId;
		}
		 $document->year = date('Y', strtotime($request->documentDate));
		 $document->month = date('m', strtotime($request->documentDate));
		 $document->date = date('d', strtotime($request->documentDate));
		 $document->grade = $request->grade;
		 $document->weight = $request->weight;
		 $document->published_as = $request->publishedAs;
		 $document->is_draft = '0';
		
		 $status = DB::transaction(function () use ($userId, $serviceKey, &$document){
			$document->save();
			
			Document::putMapping($ignoreConflicts = true);
			// print_r(1);die;
			Document::with('school', 'subject', 'teacher', 'course', 'school.country', 'ratings', 'reviews')->find($document->id)->addToIndex();
		 });
		  
		 if(is_null($status)){
			 return response()->json([
					'errorCode' => '0',
					'errorMsg' => $request->has('documentId')?'Document successfully updated':'Document successfully uploaded',
					'serviceKey' => $serviceKey,
					'data' => ""
				]);
		 }
		 
		 return response()->json([
			 'errorCode' => '1',
			 'errorMsg' => 'Server error occurred. Please try again.',
			 'data' => ''
		 ]);
	}
	
	public function addDocDraft(Request $request){
		//return response()->json($request->all());
		//Check if any parameter is empty
		 if(empty($request->serviceKey) || empty($request->userId) || empty($request->deviceType) || empty($request->image1)){
			 return response()->json([
				'errorCode' => '1',
				'errorMsg' => 'Required parameter missing.',
				'data' => ''
			]);
		 }
		
		//Check Security Key
		 $userId = $request->userId;
		 $serviceKey = $request->serviceKey;
		 $user = DB::table('users')->where([
			'id' => $userId,
			'service_key' => $serviceKey
		 ])->first();
		 if(is_null($user)){
			return response()->json([
				'errorCode' => '1',
				'errorMsg' => 'You are logged in another device.',
				'data' => ''
			]);
		 }
		 
		 //save multiple images
		 $imageFileName = [];
		 if(strtolower($request->deviceType) == 'android'){ // for android devices - image receives as string
			 $i = 0;
			 while(true){
				 $i++;
				 if($request->has('image'.$i)){
					 $randomKey = $this->generateServiceKey();
					 $result = $this->upload($randomKey, $request->deviceType, $request->input('image'.$i));
					 if($result['errorCode'] == 1){
						 return response()->json([
							'errorCode' => '1',
							'errorMsg' => $result['errorMsg'],
							'data' => ''
						]);
					 }
					 $imageFileName[] = $result['fileName'];
				 }
				 else{
					 break;
				 }
			 }
		 }
		 else{
			 $i = 0;
			 while(true){
				 $i++;
				 if($request->hasFile('image'.$i)){
					 $randomKey = $this->generateServiceKey();
					 $result = $this->upload($randomKey, $request->deviceType, 'image'.$i);
					 if($result['errorCode'] == 1){
						 return response()->json([
							'errorCode' => '1',
							'errorMsg' => $result['errorMsg'],
							'data' => ''
						]);
					 }
					 $imageFileName[] = $result['fileName'];
				 }
				 else{
					 break;
				 }
			 }
		 }
		 
		
		 $document = new Document;
		 $document->user_id = $userId;
		 $document->images = json_encode($imageFileName);
		 $document->is_draft = '1';
		
		 $status = DB::transaction(function () use ($userId, $serviceKey, &$document){
			$document->save();
			
			Document::with('school', 'subject', 'teacher', 'course', 'school.country', 'ratings', 'reviews')->find($document->id)->addToIndex();
		 });
		 
		 if(is_null($status)){
			 return response()->json([
					'errorCode' => '0',
					'errorMsg' => 'Document successfully added in draft.',
					'serviceKey' => $serviceKey,
					'data' => ""
				]);
		 }
		 
		 return response()->json([
			 'errorCode' => '1',
			 'errorMsg' => 'Server error occurred. Please try again.',
			 'data' => ''
		 ]);
	}
	
	private function upload($name, $deviceType = null, $image = null){
		$target_dir = "storage/documents/";
		if(strtolower($deviceType) == 'android'){ // for android devices - image receives as string
			$fileName = $name . date('YmdHis') . '.png';
			$targetFileName = $target_dir . $fileName;
			
			$status = file_put_contents($targetFileName, base64_decode($image));
			if($status){
				return ['errorMsg' => "File successfully uploaded.", 'errorCode' => 0, 'fileName' => url($targetFileName)];
			} else {
				return ['errorMsg' => "Sorry, there was an error uploading your file.", 'errorCode' => 1];
			}
		}
		else{ // Image receives as object
			$target_file = $target_dir . basename($_FILES[$image]["name"]);
			$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
			
			// Check if image file is a actual image or fake image		
			$check = getimagesize($_FILES[$image]["tmp_name"]);
			if($check == false) {
				return ['errorMsg' => "File is not an image.", 'errorCode' => 1];
			}
			
			// Allow certain file formats
			if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
			&& $imageFileType != "gif" ) {
				return ['errorMsg' => "Sorry, only JPG, JPEG, PNG & GIF files are allowed.", 'errorCode' => 1];
			}
			
			$fileName = $name . date('YmdHis') . '.' . $imageFileType;
			$targetFileName = $target_dir . $fileName;
			if (move_uploaded_file($_FILES[$image]["tmp_name"], $targetFileName)) {
				return ['errorMsg' => "File successfully uploaded.", 'errorCode' => 0, 'fileName' => url($targetFileName)];
			} else {
				return ['errorMsg' => "Sorry, there was an error uploading your file.", 'errorCode' => 1];
			}
		}
	}

	public function delDocument(Request $request){
		try{
	    	$validator = Validator::make($request->all(), [
				'serviceKey' => 'required',
				'userId' => 'required',
				'documentId' => 'required'
			], [
				'serviceKey.required' => 'Required parameter missing.',
				'userId.required' => 'Required parameter missing.'
			]);

			if ($validator->fails()) {
				throw new ApiException('1', $validator->errors()->first());
	        }

	        //Check Security Key
			$user = User::where([
				'id' => $request->userId,
				'service_key' => $request->serviceKey
			])->first();
			if(is_null($user)){
				throw new ApiException('2', 'You are logged in another device.');
			}

			if($user->status == '0'){
				throw new ApiException('2', 'Your account has been deactivated.');
			}

			DB::beginTransaction();
			$document = Document::where('user_id', $user->id)->where('id', $request->documentId);
			DB::table('document_favourites')->where('document_id', $request->documentId)->delete();
			DB::table('document_ratings')->where('document_id', $request->documentId)->delete();
			DB::table('reviews')->where('document_id', $request->documentId)->delete();
			DB::table('unlock_documents')->where('document_id', $request->documentId)->delete();
			$document->get()->deleteFromIndex();
			$document->delete();

			DB::commit();


			return response()->json([
				'errorCode' => '0',
				'errorMsg' => 'Document deleted.'
			]);
	    }
	    catch(ApiException $e){
	    	DB::rollback();
	    	return response()->json([
				'errorCode' => $e->errorCode,
				'errorMsg' => $e->getMessage()
			]);
	    }
	    catch(Exception $e){
	    	DB::rollback();
	    	return response()->json([
				'errorCode' => '1',
				'errorMsg' => $e->getMessage()
			]);
	    }
	}

	public function getMyDocuments(Request $request){
		try{
	    	$validator = Validator::make($request->all(), [
				'serviceKey' => 'required',
				'userId' => 'required',
				'page' => 'required'
			], [
				'serviceKey.required' => 'Required parameter missing.',
				'userId.required' => 'Required parameter missing.',
				'page.required' => 'Required parameter missing.'
			]);

			if ($validator->fails()) {
				throw new ApiException('1', $validator->errors()->first());
	        }

	        //Check Security Key
			$user = User::where([
				'id' => $request->userId,
				'service_key' => $request->serviceKey
			])->first();
			if(is_null($user)){
				throw new ApiException('2', 'You are logged in another device.');
			}

			if($user->status == '0'){
				throw new ApiException('2', 'Your account has been deactivated.');
			}

			$page = ($request->page)*$this->perPage;
			DB::beginTransaction();
			Document::putMapping($ignoreConflicts = true);

			$query = [
		 		'type' => ['document'],
		 		'body' => []
		 	];

		 	$query['body']['query']['bool']['filter'][] = ['term' => ['user_id' => $user->id]];
		 	$query['body']['query']['bool']['filter'][] = ['term' => ['status' => 1]];

		 	if($request->has('documentTitle')){
		 		$query['body']['query']['bool']['must'] = ['match' => ['_all' => $request->documentTitle]];
		 	}

			$query['body']['from'] = $page;
			$query['body']['size'] = $this->perPage;
			$query['body']['sort'][] = ['_score' => ['order' => 'desc']];
			$query['body']['sort'][] = ['id' => ['order' => 'desc']];

			$documents = Document::complexSearch($query);
			
			$documentList = [];
			foreach($documents as $q){
				$images = (Array)json_decode($q->images);
				$imageList = [];
				foreach($images as $image){
					$imageList[] = ['image' => $image];
				}

				$myRating = '0';
				$ratings = '0';
				if($q->ratings->count()){
					$ratings = ($q->ratings->sum('rating') / $q->ratings->count());
				}

				$userRate = $q->ratings->where('user_id', $user->id)->first();
				if(!is_null($userRate)){
					$myRating = $userRate->rating;
				}

 $useful_review = DB::table('reviews')->where('document_id', $q->id)->where('useful', '1')->get();
				 if(count($useful_review) != '0'){
$useful_review =count($useful_review);
					 
				 }else{
				 $useful_review ='0';	
				 }	


 $unuseful_review = DB::table('reviews')->where('document_id', $q->id)->where('useful', '0')->get();
				 if(count($unuseful_review) != '0'){
$unuseful_review =count($unuseful_review);
					 
				 }else{
				 $unuseful_review ='0';	
				 }	



				 if(!is_null($q->term_id)){
 $terms = DB::table('terms')->where('id', $q->term_id)->first();
//print_r($terms->term);die;
$term_name=$terms->term;


				 }else{
			$term_name='';	 	
				 }
 

				$documentList[] = array_map(function($v){
							return (is_null($v)) ? "" : (is_array($v)) ? $v : (String)$v;
						},[
							'documentId' => $q->id,
							'document_title' => $q->document_title,
							'document_type' => $q->document_type,
							'document_sub_title' => $q->document_sub_title,
							'course' => $q->school->is_university == '1'?$q->course->course_name:"",
							'courseId' => $q->course->id,
							'images' => $imageList,
							'school' => $q->school->school_name,
							'schoolId' => $q->school->id,
							'university' => $q->school->is_university,
							'subject' => $q->subject->subject_name,
							'subjectId' => $q->subject->id,
							'teacher' => trim($q->teacher->first_name . ' ' . $q->teacher->last_name),
							'teacherId' => $q->teacher->id,
							'year' => $q->year,
							'month' => $q->month,
							'date' => $q->date,
							'country' => $q->school->country->country_name,
							'publishedAs' => $q->published_as,
							'isDraft' => $q->is_draft,
							'favourite' => $q->favourites->where('user_id', $user->id)->count(),
							'useful_review' => $useful_review,
							'unuseful_review' => $unuseful_review,
							'myRating' => $myRating,
							'ratings' => $ratings,
							'grade' => $q->grade,
							'weight' => $q->weight,
							'published_as' => $q->published_as,
							'term_id' => $q->term_id,
							'term_name' => $term_name,
							'totalReviews' => $q->reviews->count()
						]);
			}

			DB::commit();
			
			return response()->json([
				'errorCode' => '0',
				'errorMsg' => 'Success',
				'hasRecords' => count($documentList) > 0?"1":"0",
				'data' => $documentList
			]);
	    }
	    catch(ApiException $e){
	    	DB::rollback();
	    	return response()->json([
				'errorCode' => $e->errorCode,
				'errorMsg' => $e->getMessage(),
				'data' => []
			]);
	    }
	    catch(Exception $e){
	    	DB::rollback();
	    	return response()->json([
				'errorCode' => '1',
				'errorMsg' => $e->getMessage(),
				'data' => []
			]);
	    }
	}







public function getDocumentsDetail(Request $request){
		try{
	    	$validator = Validator::make($request->all(), [
				'documentId' => 'required'
			], [
				'documentId.required' => 'Required parameter missing.'
			]);

			if ($validator->fails()) {
				throw new ApiException('1', $validator->errors()->first());
	        }

	      
			$documents = Document::where('id',$request->documentId)->get();
		
			$documentList = [];
			foreach($documents as $q){
				$images = (Array)json_decode($q->images);
				$imageList = [];
				foreach($images as $image){
					$imageList[] = ['image' => $image];
				}

				$myRating = '0';
				$ratings = '0';
				if($q->ratings->count()){
					$ratings = ($q->ratings->sum('rating') / $q->ratings->count());
				}

					

 $useful_review = DB::table('reviews')->where('document_id', $q->id)->where('useful', '1')->get();
				 if(count($useful_review) != '0'){
$useful_review =count($useful_review);
					 
				 }else{
				 $useful_review ='0';	
				 }	


 $unuseful_review = DB::table('reviews')->where('document_id', $q->id)->where('useful', '0')->get();
				 if(count($unuseful_review) != '0'){
$unuseful_review =count($unuseful_review);
					 
				 }else{
				 $unuseful_review ='0';	
				 }	



				 if(!is_null($q->term_id)){
 $terms = DB::table('terms')->where('id', $q->term_id)->first();
//print_r($terms->term);die;
$term_name=$terms->term;


				 }else{
			$term_name='';	 	
				 }

//print_r(1);die;

 if(isset($q->course->id)){
 $course_id=$q->course->id;

				 }else{
			$course_id='';	 	
				 }

				$documentList[] = array_map(function($v){
							return (is_null($v)) ? "" : (is_array($v)) ? $v : (String)$v;
						},[
							'documentId' => $q->id,
							'document_title' => $q->document_title,
							'document_type' => $q->document_type,
							'document_sub_title' => $q->document_sub_title,
							'course' => $q->school->is_university == '1'?$q->course->course_name:"",
							'courseId' => $course_id,
							'images' => $imageList,
							'school' => $q->school->school_name,
							'schoolId' => $q->school->id,
							'university' => $q->school->is_university,
							'subject' => $q->subject->subject_name,
							'subjectId' => $q->subject->id,
							'teacher' => trim($q->teacher->first_name . ' ' . $q->teacher->last_name),
							'teacherId' => $q->teacher->id,
							'year' => $q->year,
							'month' => $q->month,
							'date' => $q->date,
							'country' => $q->school->country->country_name,
							'publishedAs' => $q->published_as,
							'isDraft' => $q->is_draft,
							//'favourite' => $q->favourites->where('user_id', $user->id)->count(),
							'useful_review' => $useful_review,
							'unuseful_review' => $unuseful_review,
							'ratings' => $ratings,
							'grade' => $q->grade,
							'weight' => $q->weight,
							'published_as' => $q->published_as,
							'term_id' => $q->term_id,
							'term_name' => $term_name,
							'totalReviews' => $q->reviews->count()
						]);
			}

			DB::commit();
			
			return response()->json([
				'errorCode' => '0',
				'errorMsg' => 'Success',
				'hasRecords' => count($documentList) > 0?"1":"0",
				'data' => $documentList
			]);
	    }
	    catch(ApiException $e){
	    	DB::rollback();
	    	return response()->json([
				'errorCode' => $e->errorCode,
				'errorMsg' => $e->getMessage(),
				'data' => []
			]);
	    }
	    catch(Exception $e){
	    	DB::rollback();
	    	return response()->json([
				'errorCode' => '1',
				'errorMsg' => $e->getMessage(),
				'data' => []
			]);
	    }
	}




















	public function rateDocument(Request $request){
		try{
	    	$validator = Validator::make($request->all(), [
				'serviceKey' => 'required',
				'userId' => 'required',
				'documentId' => 'required',
				'rating' => 'required'
			], [
				'serviceKey.required' => 'Required parameter missing.',
				'userId.required' => 'Required parameter missing.',
				'documentId.required' => 'Required parameter missing.',
				'rating.required' => 'Rate the document.'
			]);

			if ($validator->fails()) {
				throw new ApiException('1', $validator->errors()->first());
	        }

	        //Check Security Key
			$user = User::where([
				'id' => $request->userId,
				'service_key' => $request->serviceKey
			])->first();
			if(is_null($user)){
				throw new ApiException('2', 'You are logged in another device.');
			}

			if($user->status == '0'){
				throw new ApiException('2', 'Your account has been deactivated.');
			}

			DB::beginTransaction();
			DB::table('document_ratings')->where('user_id', $user->id)->where('document_id', $request->documentId)->delete();

			DB::table('document_ratings')->insert(['user_id' => $user->id, 'document_id' => $request->documentId, 'rating' => $request->rating, 'updated_at' => date('Y-m-d H:i:s'), 'created_at' => date('Y-m-d H:i:s')]);

			Document::with('school', 'subject', 'teacher', 'course', 'school.country', 'ratings', 'reviews')->find($request->documentId)->addToIndex();
		 	
		 	DB::commit();

			return response()->json([
				'errorCode' => '0',
				'errorMsg' => 'Successfully Rated.',
				'rating' => $request->rating
			]);
	    }
	    catch(ApiException $e){
	    	DB::rollback();
	    	return response()->json([
				'errorCode' => $e->errorCode,
				'errorMsg' => $e->getMessage()
			]);
	    }
	    catch(Exception $e){
	    	DB::rollback();
	    	return response()->json([
				'errorCode' => '1',
				'errorMsg' => $e->getMessage()
			]);
	    }
	}

	public function addToFavourite(Request $request){
		try{
	    	$validator = Validator::make($request->all(), [
				'serviceKey' => 'required',
				'userId' => 'required',
				'documentId' => 'required',
				'favourite' => 'required' // 1 for add and 0 for remove
			], [
				'serviceKey.required' => 'Required parameter missing.',
				'userId.required' => 'Required parameter missing.',
				'documentId.required' => 'Required parameter missing.',
				'favourite.required' => 'Required parameter missing.'
			]);

			if ($validator->fails()) {
				throw new ApiException('1', $validator->errors()->first());
	        }

	        //Check Security Key
			$user = User::where([
				'id' => $request->userId,
				'service_key' => $request->serviceKey
			])->first();
			if(is_null($user)){
				throw new ApiException('2', 'You are logged in another device.');
			}

			if($user->status == '0'){
				throw new ApiException('2', 'Your account has been deactivated.');
			}

			DB::beginTransaction();
			DB::table('document_favourites')->where('user_id', $user->id)->where('document_id', $request->documentId)->delete();

			if($request->has('favourite') && $request->favourite == '1'){
				DB::table('document_favourites')->insert(['user_id' => $user->id, 'document_id' => $request->documentId]);
			}

			Document::with('school', 'subject', 'teacher', 'course', 'school.country', 'ratings', 'favourites', 'reviews')->find($request->documentId)->addToIndex();
		 	
		 	DB::commit();

			return response()->json([
				'errorCode' => '0',
				'errorMsg' => $request->favourite == '1'?'Document has been added to your favourite list.':'Document has been removed from your favourite list.'
			]);
	    }
	    catch(ApiException $e){
	    	DB::rollback();
	    	return response()->json([
				'errorCode' => $e->errorCode,
				'errorMsg' => $e->getMessage()
			]);
	    }
	    catch(Exception $e){
	    	DB::rollback();
	    	return response()->json([
				'errorCode' => '1',
				'errorMsg' => $e->getMessage()
			]);
	    }
	}

	public function review(Request $request){
		try{
	    	$validator = Validator::make($request->all(), [
				'serviceKey' => 'required',
				'userId' => 'required',
				'documentId' => 'required',
				'useful' => 'required', // 1 for useful and 0 for not useful
				//'review' => 'required',
				'reviewDate' => 'required'
			], [
				'serviceKey.required' => 'Required parameter missing.',
				'userId.required' => 'Required parameter missing.',
				'documentId.required' => 'Required parameter missing.',
				'useful.required' => 'Required parameter missing.',
				'reviewDate.required' => 'Required parameter missing.'
			]);

			if ($validator->fails()) {
				throw new ApiException('1', $validator->errors()->first());
	        }

	        //Check Security Key
			$user = User::where([
				'id' => $request->userId,
				'service_key' => $request->serviceKey
			])->first();
			if(is_null($user)){
				throw new ApiException('2', 'You are logged in another device.');
			}

			if($user->status == '0'){
				throw new ApiException('2', 'Your account has been deactivated.');
			}

			DB::beginTransaction();
			DB::table('reviews')->where('user_id', $user->id)->where('document_id', $request->documentId)->delete();







			DB::table('reviews')->insert([
					'user_id' => $user->id,
					'document_id' => $request->documentId,
					'useful' => $request->useful,
					'review' => $request->review,
					'review_date' => $request->reviewDate
				]);

			$document = Document::with('school', 'subject', 'teacher', 'course', 'school.country', 'ratings', 'favourites', 'reviews')->find($request->documentId);

			$score = 0;

//keshav
			//$downvots = $document->reviews->where('useful', 0)->count() + 5;
			//$upvots = $document->reviews->where('useful', 1)->count() + 5;

			
			//$score = (($upvots + 1.9208) / ($upvots + $downvots) - 1.96 * sqrt(($upvots * $downvots) / ($upvots + $downvots) + 0.9604) / ($upvots + $downvots)) / (1 + 3.8416 / ($upvots + $downvots));

//vipul
			$downvots = $document->reviews->where('useful', 0)->count();
			$upvots = $document->reviews->where('useful', 1)->count();

			$score=(($upvots + 1.9208) / ($upvots + $downvots) - 
                   1.96 * SQRT(($upvots * $downvots) / ($upvots + $downvots) + 0.9604) / 
                          ($upvots + $downvots)) / (1 + 3.8416 / ($upvots + $downvots));

			$document->score = $score;
			$document->save();

			$document->addToIndex();
		 	
		 	DB::commit();







 $useful_review = DB::table('reviews')->where('document_id', $request->documentId)->where('useful', '1')->get();
				 if(count($useful_review) != '0'){
$useful_review =count($useful_review);
					 
				 }else{
				 $useful_review ='0';	
				 }	

 $unuseful_review = DB::table('reviews')->where('document_id', $request->documentId)->where('useful', '0')->get();
				 if(count($unuseful_review) != '0'){
$unuseful_review =count($unuseful_review);
					 
				 }else{
				 $unuseful_review ='0';	
				 }	








			return response()->json([
				'errorCode' => '0',
				'errorMsg' => 'Document successfully reviewed.',
				'data' => [array_map(function($v){
							return (is_null($v)) ? "" : (is_array($v)) ? $v : (String)$v;
						},[
							'userName' => $user->nick_name,
							'useful' => $request->useful,
							'review' => $request->review,
							'reviewDate' => $request->reviewDate,
							'useful_review'=>$useful_review,
							'unuseful_review'=>$unuseful_review
						])]
			]);
	    }
	    catch(ApiException $e){
	    	DB::rollback();
	    	return response()->json([
				'errorCode' => $e->errorCode,
				'errorMsg' => $e->getMessage(),
				'data' => []
			]);
	    }
	    catch(Exception $e){
	    	DB::rollback();
	    	return response()->json([
				'errorCode' => '1',
				'errorMsg' => $e->getMessage(),
				'data' => []
			]);
	    }
	}

	public function delReview(Request $request){
		try{
	    	$validator = Validator::make($request->all(), [
				'serviceKey' => 'required',
				'userId' => 'required',
				'documentId' => 'required'
			], [
				'serviceKey.required' => 'Required parameter missing.',
				'userId.required' => 'Required parameter missing.',
				'documentId.required' => 'Required parameter missing.'
			]);

			if ($validator->fails()) {
				throw new ApiException('1', $validator->errors()->first());
	        }

	        //Check Security Key
			$user = User::where([
				'id' => $request->userId,
				'service_key' => $request->serviceKey
			])->first();
			if(is_null($user)){
				throw new ApiException('2', 'You are logged in another device.');
			}

			if($user->status == '0'){
				throw new ApiException('2', 'Your account has been deactivated.');
			}

			DB::beginTransaction();
			$review = Review::where('user_id', $user->id)->where('document_id', $request->documentId)->delete();

			$document = Document::with('school', 'subject', 'teacher', 'course', 'school.country', 'ratings', 'favourites', 'reviews')->find($request->documentId);

			$score = 0;
			$downvots = $document->reviews->where('useful', 0)->count() + 5;
			$upvots = $document->reviews->where('useful', 1)->count() + 5;
			$score = (($upvots + 1.9208) / ($upvots + $downvots) - 1.96 * sqrt(($upvots * $downvots) / ($upvots + $downvots) + 0.9604) / ($upvots + $downvots)) / (1 + 3.8416 / ($upvots + $downvots));

			$document->score = $score;
			$document->save();

			$document->addToIndex();
		 	
		 	DB::commit();




 $useful_review = DB::table('reviews')->where('document_id', $request->documentId)->where('useful', '1')->get();
				 if(count($useful_review) != '0'){
$useful_review =count($useful_review);
					 
				 }else{
				 $useful_review ='0';	
				 }	

 $unuseful_review = DB::table('reviews')->where('document_id', $request->documentId)->where('useful', '0')->get();
				 if(count($unuseful_review) != '0'){
$unuseful_review =count($unuseful_review);
					 
				 }else{
				 $unuseful_review ='0';	
				 }	





			return response()->json([
				'errorCode' => '0',
				'useful_review' => $useful_review,
				'unuseful_review' => $unuseful_review,
				'errorMsg' => 'Successfully Deleted.'
			]);
	    }
	    catch(ApiException $e){
	    	DB::rollback();
	    	return response()->json([
				'errorCode' => $e->errorCode,
				'errorMsg' => $e->getMessage()
			]);
	    }
	    catch(Exception $e){
	    	DB::rollback();
	    	return response()->json([
				'errorCode' => '1',
				'errorMsg' => $e->getMessage()
			]);
	    }
	}

	public function getReviews(Request $request){
		try{
	    	$validator = Validator::make($request->all(), [
				'serviceKey' => 'required',
				'userId' => 'required',
				'documentId' => 'required'
			], [
				'serviceKey.required' => 'Required parameter missing.',
				'userId.required' => 'Required parameter missing.',
				'documentId.required' => 'Required parameter missing.'
			]);

			if ($validator->fails()) {
				throw new ApiException('1', $validator->errors()->first());
	        }

	        //Check Security Key
			$user = User::where([
				'id' => $request->userId,
				'service_key' => $request->serviceKey
			])->first();
			if(is_null($user)){
				throw new ApiException('2', 'You are logged in another device.');
			}

			if($user->status == '0'){
				throw new ApiException('2', 'Your account has been deactivated.');
			}

			DB::beginTransaction();
			$documentReviews = Review::with('user')->where('document_id', $request->documentId)->orderBy('review_date', 'desc')->get()->map(function($q) use($user){
				return array_map(function($v){
							return (is_null($v)) ? "" : (is_array($v)) ? $v : (String)$v;
						},[
							'nickName' => $q->user->nick_name,
							'userImage' => $q->user->image,
							'useful' => $q->useful,
							'review' => $q->review,
							'reviewDate' => $q->review_date,
							'myReview' => $q->user_id == $user->id?"1":"0"
						]);
			});
		 	
		 	DB::commit();

			return response()->json([
				'errorCode' => '0',
				'errorMsg' => 'Success',
				'data' => $documentReviews
			]);
	    }
	    catch(ApiException $e){
	    	DB::rollback();
	    	return response()->json([
				'errorCode' => $e->errorCode,
				'errorMsg' => $e->getMessage(),
				'data' => []
			]);
	    }
	    catch(Exception $e){
	    	DB::rollback();
	    	return response()->json([
				'errorCode' => '1',
				'errorMsg' => $e->getMessage(),
				'data' => []
			]);
	    }
	}

	public function unlockDocument(Request $request){
		try{
			$validator = Validator::make($request->all(), [
				'serviceKey' => 'required',
				'userId' => 'required',
				'documentId' => 'required',
				'points' => 'required'
			], [
				'serviceKey.required' => 'Required parameter missing.',
				'userId.required' => 'Required parameter missing.',
				'documentId.required' => 'Required parameter missing.',
				'points.required' => 'Required parameter missing.'
			]);

			if ($validator->fails()) {
				throw new ApiException('1', $validator->errors()->first());
	        }

	        //Check Security Key
			$user = User::where([
				'id' => $request->userId,
				'service_key' => $request->serviceKey
			])->first();
			if(is_null($user)){
				throw new ApiException('2', 'You are logged in another device.');
			}

			if($user->status == '0'){
				throw new ApiException('2', 'Your account has been deactivated.');
			}

			if($user->points < $request->points){
				throw new ApiException('1', 'You have not sufficient points to unlock the document.');
			}

			DB::beginTransaction();
			$document = Document::with('user')->find($request->documentId);
			DB::table('unlock_documents')->insert(['user_id' => $user->id, 'document_id' => $document->id]);

			DB::table('document_favourites')->insert(['user_id' => $user->id, 'document_id' => $document->id]);

if($document->score == '0'){
$document->score='1';
}
			$points = 0;
	//print_r($document->score);die;
			if($document->score > 0){
					
				//$ki = sqrt($document->score) * pow(2,1) - 1;
				$ki = sqrt($document->score) * pow(2,0) ;
				$k = $ki;
				//print_r($document->score);die;
				$points = (0.4 * 30) + ($ki / $k);
				//print_r(2);die;
			}

			//DB::table('point_histories')->insert(['user_id' => $document->user->id, 'document_id' => $document->id, 'message' => $user->first_name.' is unlocked '.$document->document_title, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), 'points' => $points]);

//print_r($points);die;
			

			DB::table('point_histories')->insert(['user_id' =>$document->user->id, 'document_id' => $document->id, 'message' => $user->nick_name.' downloaded '.$document->document_title, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), 'points' => $points]);

			DB::table('point_histories')->insert(['user_id' =>$user->id, 'document_id' => $document->id, 'message' => 'You unlocked '.$document->document_title, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), 'points' => '30']);



//print_r($points);die;
			$document->user->points = $document->user->points + $points;
			$document->user->save();

			$user->points = $user->points - $request->points;
			//$user->points = $user->points - 30;
			$user->save();
			DB::commit();

			return response()->json([
				'errorCode' => '0',
				'errorMsg' => 'Successfully unlocked.',
				'points' => (String)$user->points
			]);
		}
		catch(ApiException $e){
	    	DB::rollback();
	    	return response()->json([
				'errorCode' => '1',
				'errorMsg' => $e->getMessage(),
				'points' => ""
			]);
	    }
	    catch(Exception $e){
	    	DB::rollback();
	    	return response()->json([
				'errorCode' => '1',
				'errorMsg' => $e->getMessage(),
				'points' => ""
			]);
	    }
	}
}
