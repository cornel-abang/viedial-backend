<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PhyCategory;
use App\Models\Workout;
use App\Models\PhyComment;
use App\Models\WorkoutComment;
use App\Models\WorkoutTracker;
use Carbon\Carbon;

class PhysicalController extends Controller
{
    public $user;

	public function __construct()
	{
		$this->user = auth()->user();
	}

    public function createSeries(Request $request)
    {
        $validator = $this->validateSeries($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()], 422);
        }

        $serie = new PhyCategory;

        if ((int) $request->phy_id > 0) {
            $serie = PhyCategory::find($request->phy_id);
            $serie->update($request->only('title'));
        }else{
            $serie = Serie::create($request->only('title'));
        }

        if ($request->has('videos')) {
            $this->saveVidoes($request, $serie->id, true);
        }
        return response()->json(['res_type'=>'success', 'message'=>'Videos added']);
    }

    public function validateSeries(Request $request)
    {
        $msg = [
            'title.required' => 'Title is required',
            'title.string'   => 'Title must be valid text',
        ];

        return validator()->make($request->all(), [
            'title' => 'required|string',
        ],$msg);
    }

    public function saveVidoes(Request $request, $series_id, $w = false)
    {
        $vidArr = [];
        foreach ($request['videos'] as $key => $value) {
            $vidName = time().'.'.$request->videos[$key]->extension();  
            $request->videos[$key]->move('assets/vids/phy', $vidName);
            array_push($vidArr, [
                'phy_category_id' => $series_id,
                'title'    => $request['titles'][$key],
                'workout_url'=> asset('assets/vids/phy/'.$vidName),
            ]);
        }

        for ($i = 0; $i < count($vidArr); $i++) { 
            Workout::create($vidArr[$i]);
        }

        // call was from within the class - another function
        if ($w) {
            return true;
        }

        return response()->json(['res_type'=>'success', 'message'=>'Video added']);
    }

    public function getActivity($id)
    {
        return response()->json(['res_type'=>'success', 'phy'=>PhyCategory::find($id)]);
    }

    public function index()
    {
    	$workout_series = [];
    	$series = PhyCategory::all();
    	if ($series->isEmpty()) {
    		return response()->json(['res_type'=>'no content', 'message'=>'No workout series yet']);
    	}
    	foreach ($series as $serie) {
    		$data = [
    			'id'			=> $serie->id,
    			'title' 		=> $serie->title,
    			'workout_count'	=> $serie->workouts->count(),
    			'likes'			=> $serie->likes,
    			'dislikes'		=> $serie->dislikes,
    			'comments_count'=> $serie->comments->count(),
                'created_at'    => $serie->created_at,
    		];
    		array_push($workout_series, $data);
    	}
    	return response()->json(['res_type'=>'success', 'series'=>$workout_series]);
    }

    public function newIndex()
    {
        $workouts = Workout::all();
        if ($workouts->isEmpty()) {
            return response()->json(['res_type'=>'not found', 'message'=>'No workout videos yet']);
        }
        $vidData = [];
        foreach ($workouts as $video) {
            $done = false;
            $tracked = WorkoutTracker::whereDate('created_at', Carbon::today())
            ->where('workout_id', $video->id)
            ->where('user_id', $this->user->id)
            ->first();
            if ($tracked) {
                $done = true;
            }
            $data = [
                'id'         => $video->id,
                'title'      => $video->title,
                'workout_url'=> $video->workout_url,
                'calorie_burn'=> $video->calorie_burn,
                'done'        => $done,
                'likes'      => $video->likes,
                'dislikes'   => $video->dislikes,
                'comments_count'=> $video->comments->count(),
                'created_at'    => $video->created_at,
            ];
            array_push($vidData, $data);
        }

        return response()->json(['res_type'=>'success', 'videos'=>$vidData]);
    }

    public function seriesWorkouts($id)
    {
    	$serie = PhyCategory::find($id);

    	if (!$serie) {
    		return response()->json(['res_type'=>'not found', 'message'=>'Workout series not found.'],404);
    	}

    	if ($serie->workouts->isEmpty()) {
    		return response()->json(['res_type'=>'not found', 'message'=>'No videos yet for this workout series.']);
    	}

    	$vidData = [];
    	foreach ($serie->workouts as $video) {
            $done = false;
            $tracked = WorkoutTracker::whereDate('created_at', Carbon::today())
            ->where('workout_id', $video->id)
            ->where('user_id', $this->user->id)
            ->first();
            if ($tracked) {
                $done = true;
            }
    		$data = [
    			'id'		 => $video->id,
    			'serie_id'	 => $video->serie_id,
    			'title'		 => $video->title,
    			'workout_url'=> $video->workout_url,
                'calorie_burn'=> $video->calorie_burn,
                'done'        => $done,
    			'likes'		 => $video->likes,
    			'dislikes'	 => $video->dislikes,
    			'comments_count'=> $video->comments->count(),
                'created_at'    => $video->created_at,
    		];
    		array_push($vidData, $data);
    	}

    	return response()->json(['res_type'=>'success', 'videos'=>$vidData]);
    }

    public function seriesComments($id)
    {
    	$serie = PhyCategory::find($id);

    	if (!$serie) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'Workout series not found.'],404);
    	}

    	if ($serie->comments->isEmpty()) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'No comments yet for this workout series.'],404);
    	}

    	$commData = [];

    	foreach ($serie->comments as $comm) {
    		$data = [
    			'id'		 => $comm->id,
    			'serie_id'	 => $comm->serie_id,
    			'by'	 	 => $comm->user->annon_name,
    			'comment_text'	 => $comm->comment_text,
                'created_at'      => $comm->created_at,
    		];
    		array_push($commData, $data);
    	}

    	return response()->json(['res_type'=>'success', 'comments'=>$commData]);
    }

    public function commentOnPhy(Request $request, $id)
    {
    	$validator = $this->validateComment($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()],422);
        }

        $comment = New PhyComment;
        $comment->phy_category_id = $id;
        $comment->user_id  = $this->user->id;
        $comment->comment_text = $request->comment_text;
        $comment->save();

        return response()->json(['res_type'=>'success', 'message'=>'Commented']);
    }

    public function validateComment(Request $request)
    {
        $msg = [
            'comment_text.required' => 'Please enter a comment',
        ];
        return validator()->make($request->all(), [
            'comment_text' => 'required',
        ], $msg);
    }

    public function likeSeries($id)
    {
    	$serie = PhyCategory::find($id);

    	if (!$serie) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'Workout series not found.'],404);
    	}

    	$serie->likes = $series->likes+1;
    	$serie->save();

    	return response()->json(['res_type'=>'success', 'message'=>'Series liked']);
    }

    public function dislikeSeries($id)
    {
    	$serie = PhyCategory::find($id);

    	if (!$serie) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'Workout series not found.'],404);
    	}

    	$serie->dislikes = $series->dislikes+1;
    	$serie->save();

    	return response()->json(['res_type'=>'success', 'message'=>'Series disliked']);
    }

    public function commentOnWorkout(Request $request, $id)
    {
    	$validator = $this->validateComment($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()],422);
        }

        $comment = New WorkoutComment;
        $comment->workout_id = $id;
        $comment->user_id  = $this->user->id;
        $comment->comment_text = $request->comment_text;
        $comment->save();

        return response()->json(['res_type'=>'success', 'message'=>'Commented']);
    }

    public function workoutComments($id)
    {
    	$workout = Workout::find($id);

    	if (!$workout) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'workout not found.'],404);
    	}

    	if ($workout->comments->isEmpty()) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'No comments yet for this workout.'],404);
    	}

    	$commData = [];

    	foreach ($workout->comments as $comm) {
    		$data = [
    			'id'		 => $comm->id,
    			'workout_id' => $comm->workout_id,
    			'by'	 	 => $comm->user->annon_name,
    			'comment_text'	 => $comm->comment_text,
                'created_at'      => $comm->created_at,
    		];
    		array_push($commData, $data);
    	}

    	return response()->json(['res_type'=>'success', 'comments'=>$commData]);
    }

    public function likeVideo($id)
    {
    	$video = Workout::find($id);

    	if (!$video) {
    		return response()->json(['res_type'=>'not found', 'message'=>'Workout not found.']);
    	}

    	$video->likes = $video->likes+1;
    	$video->save();

    	return response()->json(['res_type'=>'success', 'message'=>'Workout liked']);
    }

    public function dislikeVideo($id)
    {
    	$video = Workout::find($id);

    	if (!$video) {
    		return response()->json(['res_type'=>'not found', 'message'=>'Workout not found']);
    	}

    	$video->dislikes = $video->dislikes+1;
    	$video->save();

    	return response()->json(['res_type'=>'success', 'message'=>'Workout disliked']);
    }

    public function doneWorkout($id, $w = false)
    {
        $video = Workout::find($id);
        if (!$video) {
            return response()->json(['res_type'=>'not found', 'message'=>'Workout not found']);
        }

        WorkoutTracker::create([
            'user_id' => $this->user->id,
            'workout_id'=> $id
        ]);

        if ( $this->user->goal() ) {
            $this->user->goal()->calorie_burned_this_week += $video->calorie_burn;
            $this->user->goal()->save();
        }

        if ($w) {
            return true;
        }

        if ( $this->user->goal()->calorie_burned_this_week >= $this->user->goal()->weekly_calorie_def ) {
            //reset weekly burn
            $this->user->goal()->calorie_burned_this_week = 0;
            $this->user->goal()->save();

            return response()->json(['res_type'=>'success', 'message'=>'Well done! You have met your weekly calorie goal.']);
        }

        return response()->json(['res_type'=>'success', 'message'=>'Workout done']);
    }

    public function multiDoneWorkouts(Request $request)
    {
        foreach ($request->videos as $vid) {
            $this->doneWorkout($vid, true);
        }

        if ( $this->user->goal()->calorie_burned_this_week >= $this->user->goal()->weekly_calorie_def ) {
            //reset weekly burn
            $this->user->goal()->calorie_burned_this_week = 0;
            $this->user->goal()->save();
            return response()->json(['res_type'=>'success', 'message'=>'Well done! You have met your weekly calorie goal.']);
        }

        return response()->json(['res_type'=>'success', 'message'=>'Workouts done']);
    }

    public function undoWorkout($id, $w = false)
    {
        $video = Workout::find($id);
        if (!$video) {
            return response()->json(['res_type'=>'not found', 'message'=>'Workout not found']);
        }

        $tracked = WorkoutTracker::whereDate('created_at', Carbon::today())
        ->where('workout_id', $id)
        ->where('user_id', $this->user->id)
        ->first();

        if (!$tracked) {
            if ($w) {
                return false;
            }
            return response()->json(['res_type'=>'not done', 'message'=>'Workout was previously not done']);
        }

        if ( $this->user->goal() ) {
            $this->user->goal()->calorie_burned_this_week -= $video->calorie_burn;
            $this->user->goal()->save();
        }

        $tracked->delete();

        if ($w) {
            return true;
        }

        return response()->json(['res_type'=>'success', 'message'=>'Workout undone']);
    }

    public function multiUnDoneWorkouts(Request $request)
    {
        foreach ($request->videos as $vid) {
            $this->undoWorkout($vid, true);
        }

        return response()->json(['res_type'=>'success', 'message'=>'Workouts undone']);
    }

    public function destroyWorkout($id)
    {
        $workout = Workout::find($id);
        $workout->delete();
        return response()->json(['res_type'=>'success']);
    }

    public function destroyPhysicalActivity($id)
    {
        $phy = PhyCategory::find($id);
        $phy->delete();
        return response()->json(['res_type'=>'success']);
    }
}
