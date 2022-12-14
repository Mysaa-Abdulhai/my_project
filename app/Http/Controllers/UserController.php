<?php

namespace App\Http\Controllers;
use App\Enums;
use App\Models\campaign_like;
use App\Models\campaignSkill;
use App\Models\favorite;
use App\Models\point;
use App\Models\points_convert_request;
use App\Models\Profile;
use App\Models\profileSkill;
use App\Models\public_comment;
use App\Models\public_like;
use App\Models\public_post;
use App\Models\User;
use App\Models\user_role;
use App\Models\volunteer;
use App\Models\Campaign_Post;
use App\Models\volunteer_campaign_rate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\volunteer_campaign_request;
use App\Models\donation_campaign_request;
use Illuminate\Http\Request;
use App\Models\volunteer_campaign;
use App\Models\location;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function show_volunteer_campaign(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'category' => 'required|in:natural,human,pets,others,all',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        if($request->category=='all')
        {
            $campaign=collect();
            $v_campaigns=volunteer_campaign::select('id', 'name', 'image', 'type', 'details', 'volunteer_number', 'current_volunteer_number')->get();
            foreach ($v_campaigns as $v_campaign) {
                if(volunteer_campaign_rate::where('volunteer_campaign_id','=',$v_campaign->id)->exists())
                {
                    $rating = collect();
                    $rates = volunteer_campaign_rate::where('volunteer_campaign_id', '=', $v_campaign->id)->get();
                    foreach ($rates as $rate) {
                        $rating->push($rate->rate);
                    }
                    $rating = $rating->avg();
                    $campaign->push(['id'=>$v_campaign->id,'name'=>$v_campaign->name,
                        'image'=>$v_campaign->image,'type'=>$v_campaign->type,
                        'details'=>$v_campaign->details,
                        'volunteer_number'=>$v_campaign->volunteer_number,
                        'current_volunteer_number'=>$v_campaign->current_volunteer_number,
                        'rate'=>$rating]);
                }
                else
                    $campaign->push(['id'=>$v_campaign->id,'name'=>$v_campaign->name,
                        'image'=>$v_campaign->image,'type'=>$v_campaign->type,
                        'details'=>$v_campaign->details,
                        'volunteer_number'=>$v_campaign->volunteer_number,
                        'current_volunteer_number'=>$v_campaign->current_volunteer_number,
                        'rate'=>0]);
            }
            return response()->json([
                'campaigns' => $campaign,
            ], 200);
        }
        if(volunteer_campaign::select('id', 'name', 'image', 'type', 'details', 'volunteer_number', 'current_volunteer_number')->where('type', '=', $request->category)->exists()) {

            $campaign=collect();
            $v_campaigns=volunteer_campaign::select('id', 'name', 'image', 'type', 'details', 'volunteer_number', 'current_volunteer_number')->where('type', '=', $request->category)->get();
            foreach ($v_campaigns as $v_campaign) {
                if(volunteer_campaign_rate::where('volunteer_campaign_id','=',$v_campaign->id)->exists())
                {
                    $rating = collect();
                    $rates = volunteer_campaign_rate::where('volunteer_campaign_id', '=', $v_campaign->id)->get();
                    foreach ($rates as $rate) {
                        $rating->push($rate->rate);
                    }
                    $rating = $rating->avg();
                    $campaign->push(['id'=>$v_campaign->id,'name'=>$v_campaign->name,
                        'image'=>$v_campaign->image,'type'=>$v_campaign->type,
                        'details'=>$v_campaign->details,
                        'volunteer_number'=>$v_campaign->volunteer_number,
                        'current_volunteer_number'=>$v_campaign->current_volunteer_number,
                        'rate'=>$rating]);

                }
                else
                {
                    $campaign->push(['id' => $v_campaign->id, 'name' => $v_campaign->name,
                        'image' => $v_campaign->image, 'type' => $v_campaign->type,
                        'details' => $v_campaign->details,
                        'volunteer_number' => $v_campaign->volunteer_number,
                        'current_volunteer_number' => $v_campaign->current_volunteer_number,
                        'rate' => 0]);
                }
            }
            return response()->json([
                'campaigns' => $campaign,
            ], 200);
        }
        else
            return response()->json([
                'message' => 'no any campaign in this category',
            ], 403);
    }
    public function show_details_of_volunteer_campaign(Request $request){
        $validator = Validator::make($request->all(), [
            'id'     => 'required|int',
        ]);
        if ($validator->fails())
            return response()->json($validator->errors()->toJson(), 400);
        if(volunteer_campaign::where('id', $request->id)->exists())
        {
            $campaign = volunteer_campaign::where('id', $request->id)->first();
            $skills=$campaign->getSkill();

            if(volunteer_campaign_rate::where('volunteer_campaign_id','=',$campaign->id)->exists())
            {
                $rate = collect();
                $rates = volunteer_campaign_rate::where('volunteer_campaign_id', '=', $campaign->id)->get();
                foreach ($rates as $rat) {
                    $rate->push($rat->rate);
                }
                $rate = $rate->avg();
            }
            else
                $rate=0;

            $leader=volunteer::where('volunteer_campaign_id','=',$campaign->id)
                ->where('is_leader','=',true)
                ->select('user_id')
                ->first();
            $id=$leader->user_id;
            $name=User::select('name')->where('id','=',$id)->first();
            $is_joined=volunteer::where('volunteer_campaign_id','=',$campaign->id)->where('user_id','=',auth()->user()->id)->exists();
            $location=location::where('id','=',$campaign->location_id)->first();
            return response()->json([
                'id'=>$campaign->id,
                'name'=>$campaign->name,
                'image'=>$campaign->image,
                'details'=>$campaign->details,
                'type'=>$campaign->type,
                'city'=>$location->city,
                'country'=>$location->country,
                'street'=>$location->street,
                'maxDate'=>$campaign->maxDate,
                'maxDate_days'=>Carbon::parse(Carbon::now())->diff($campaign->maxDate)->days,
                'leader_name' => $name->name,
                'age'=>$campaign->age,
                'study'=>$campaign->study,
                'skills'=>$skills,
                'rate'=>$rate,
                'current_volunteer_number' => $campaign->current_volunteer_number,
                'volunteer_number' => $campaign->volunteer_number,
                'is_joined' => $is_joined,
            ], 200);
        }
        else
            return response()->json([
                'message' => 'your campaign not found',
            ], 400);
    }//end 

    
    public function volunteer_campaign_request(Request $request){
        $validator= Validator::make($request->all(), [
            'name'=>'required|string',
            'image'=>'required',
            'details'=>'required|string',
            'type'     => 'required|string',
            'volunteer_number'     => 'required|int',
            'maxDate'     => 'required|date',
            'country'  => 'required|string',
            'city'  => 'required|string',
            'street'  => 'required|string',
            'longitude' => 'required|numeric|between:-90.00000000,90.00000000',
            'latitude' => 'required|numeric|between:-90.00000000,90.00000000',
        ]);
        if ($validator->fails())
            return response()->json($validator->errors()->toJson(), 400);




        //image
        $image = $request->file('image');
        $image_name = time() . '.' . $image->getClientOriginalExtension();
        $image->move('images', $image_name);

        $location=new location();
        $location->country=$request->country;
        $location->city=$request->city;
        $location->street=$request->street;
        $location->save();

        $campaign_request=new volunteer_campaign_request();
        $campaign_request->name=$request->name;
        $campaign_request->type=$request->type;
        $campaign_request->details =$request->details;
        $campaign_request->volunteer_number=$request->volunteer_number;
        $campaign_request->maxDate=$request->maxDate;
        $campaign_request->image=$image_name;
        $campaign_request->user_id=auth()->user()->id;
        $campaign_request->location_id=$location->id;
        $campaign_request->longitude=$request->longitude;
        $campaign_request->latitude=$request->latitude;
        $campaign_request->save();

        return response()->json([
                    'message'  => 'request added Successfully',
                    'campaign_request'  => $campaign_request,
        ],200);
    }
    public function donation_campaign_request(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'required|string',
            'total_value' => 'required|int',
            'end_at' => 'required|int',
            'image' => 'required|string',
        ]);
        if ($validator->fails())
            return response()->json($validator->errors()->toJson(), 400);

        $campaign_request = new donation_campaign_request();
        $campaign_request->name = $request->name;
        $campaign_request->description = $request->description;
        $campaign_request->total_value = $request->total_value;
        $campaign_request->end_at = $request->end_at;
        $campaign_request->image = $request->image;
        $campaign_request->user_id = auth()->user()->id;
        $campaign_request->save();
        return response()->json([
            'message' => 'request added Successfully',
            'campaign_request' => $campaign_request,
        ], 200);
    }
    public function show_public_posts(Request $request){
        $po=public_post::all();
        $posts=collect();
        foreach ($po as$post)
        {
            $like=public_like::where('public_post_id','=',$post->id)->count();
            $comments=collect();
            $comm=public_comment::where('public_post_id','=',$post->id)->get();
            foreach ($comm as $com)
            {
                if(Profile::where('user_id','=',$com->user_id)->exists())
                {
                    $name=User::where('id','=',$com->user_id)->select('name')->first();
                    $image=Profile::where('user_id','=',$com->user_id)->select('image')->first();
                    $comments->push(['text'=>$com->text,'name'=>$name->name,
                        'image'=>$image->image]);
                }
                else
                {
                    $name=User::where('id','=',$com->user_id)->select('name')->first();
                    $comments->push(['text'=>$com->text,'name'=>$name->name,
                        'image'=>null]);
                }
            }

            $is_liked=public_like::where('public_post_id','=',$post->id)->where('user_id','=',auth()->user()->id)->exists();
            $posts->push(['id'=>$post->id,'title'=>$post->title,
                'body'=>$post->body,'image'=>$post->image,
                'likes'=>$like,
                'comment'=>$comments,
                'is_liked'=>$is_liked]);
        }
        return response()->json([
            'post'  => $posts,
        ],200);
    }
    public function show_posts_of_campaign(Request $request){
        $validator=Validator::make($request->all(),[
            'id' => 'required|int',
        ]);

        if ($validator->fails())
            return response()->json($validator->errors()->toJson(), 400);
        $po=Campaign_Post::where('volunteer_campaign_id',$request->id)->get();
        $posts=collect();
        foreach ($po as $post)
        {
            $like=campaign_like::where('Campaign_Post_id','=',$post->id)->count();
            $is_liked=campaign_like::where('Campaign_Post_id','=',$post->id)->where('user_id','=',auth()->user()->id)->exists();
            $posts->push(['id'=>$post->id,'title'=>$post->title,
                'body'=>$post->body,'image'=>$post->image,
                'volunteer_campaign_id'=>$post->volunteer_campaign_id,
                'likes'=>$like,'is_liked'=>$is_liked
            ]);
        }
        return response()->json([
            'post' =>$posts,
            'message' => 'all posts for campaign number'
        ],200);
    }
    public function join_campaign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'campaign_id' => 'required|int',
        ]);

        if ($validator->fails())
            return response()->json($validator->errors()->toJson(), 400);

        $pro=Profile::where('user_id', '=', auth()->user()->id)->first();
        $camp=volunteer_campaign::where('id','=',$request->campaign_id)->first();
        $campSkills=campaignSkill::where('volunteer_campaign_id','=',$camp->id)->pluck('name');
        $proSkills=profileSkill::where('Profile_id','=',$pro->id)->pluck('name');
        $campSkills->toArray();
        $proSkills->toArray();
        $accept=false;
        if($campSkills->diff($proSkills)->isEmpty())
        {
            $accept=true;
        }
        $age = Carbon::parse($pro->birth_date)->diff(Carbon::now())->y;
            if($pro->study==$camp->study
            &&$accept==true
            &&$age>=$camp->age
            )
            {

                $volunteer = new volunteer;
                $volunteer->user_id = auth()->user()->id;
                $volunteer->volunteer_campaign_id = $request->campaign_id;
                $volunteer->save();
                if(user_role::where('user_id','=',auth()->user()->id)->where('role_id','=',4)->exists()==false) {
                    $user_role = new user_role([
                        'user_id' => auth()->user()->id,
                        'role_id' => 4
                    ]);
                    $user_role->save();
                }
                $camp->update(['current_volunteer_number'=>$camp->current_volunteer_number++]);
                $camp->save();

                return response()->json([
                    'message' => 'you join the campaign'
                ], 200);
            }

            else
              {
                  return response()->json([
                   'message' => 'you haven\'t the requirement of campaign'
              ], 400);
           }

    }
    public function add_profile(Request $request){
        $validator = Validator::make($request->all(),[
            'name'       => 'required|string',
            'bio'       => 'required|string',
            'study'       => 'required|string',
            'image'       => 'required',
        ]);
        if ($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        //image
        $image = $request->file('image');
        $image_name = time() . '.' . $image->getClientOriginalExtension();
        $image->move('images', $image_name);



        $user_pro = new Profile();
        $user_pro->name      = $request->name;
        $user_pro->bio  = $request->bio;
        $user_pro->study  = $request->study;
        $user_pro->image       = $image_name ;
        $user_pro->user_id     = auth()->user()->id;

        if(!is_null($request->gender))
        {
            $user_pro->gender = $request->gender ;
        }
        if(!is_null($request->birth_date))
        {
            $user_pro->birth_date = $request->birth_date     ;
        }
        if(!is_null($request->leaderInFuture))
        {
            $user_pro->leaderInFuture = $request->leaderInFuture ;
        }
        if(!is_null($request->phoneNumber))
        {
            $user_pro->phoneNumber = $request->phoneNumber ;
        }

        $user_pro->save();

        if(!is_null($request->skills))
        {
            $array=$request->skills;
            $array=explode(",",$array);
            foreach($array as $skill)
            {
                profileSkill::create(['name'=>$skill,'Profile_id'=>$user_pro->id]);
            }
        }
        $skills=profileSkill::where('Profile_id','=',$user_pro->id)->pluck('name');
        return response()->json([
            'your_profile' => $user_pro,
            'skills'=>$skills,
            'message' => ' your profile created successfully '
        ],200);
    }
    public function update_profile(Request $request){

        $id=auth()->user()->id;
        if(Profile::where('user_id','=',$id)->exists())
        {
            $pro=Profile::where('user_id','=',$id)->first();
            if(is_null($request->image) And is_null($request->birth_date) And is_null($request->gender)
                And is_null($request->bio)And is_null($request->name)
                And is_null($request->study) And is_null($request->skills)And is_null($request->leaderInFuture)
                And is_null($request->phoneNumber)
            ){
                return response()->json([
                    'message' => 'enter information to update your profile',
                    'campaign is' => $pro
                ]);
            }
            if(!is_null($request->image))
            {
                //image
                $image = $request->file('image');
                $image_name = time() . '.' . $image->getClientOriginalExtension();
                $image->move('images', $image_name);
                $pro->image = $image_name ;
                $pro->save();
            }
            if(!is_null($request->birth_date))
            {
                $pro->birth_date = $request->birth_date ;
                $pro->save();
            }
            if(!is_null($request->name))
            {
                $pro->name = $request->name ;
                $pro->save();
            }
            if(!is_null($request->gender)) {
                $pro->gender = $request->gender;
                $pro->save();
            }
            if(!is_null($request->bio))
            {
                $pro->bio = $request->bio ;
                $pro->save();
            }

            if(!is_null($request->study))
            {
                $pro->study = $request->study ;
                $pro->save();
            }
            if(!is_null($request->skills))
            {
                profileSkill::select('name')->where('Profile_id','=',$pro->id)->delete();
                $array=$request->skills;
                $array=explode(",",$array);
                foreach($array as $skill)
                {
                    profileSkill::create(['name'=>$skill,'Profile_id'=>$pro->id]);
                }
                $skills=profileSkill::select('name')->where('Profile_id','=',$pro->id)->get();
            }
            if(!is_null($request->leaderInFuture))
            {
                $pro->leaderInFuture = $request->leaderInFuture ;
                $pro->save();

            }
            if(!is_null($request->phoneNumber))
            {
                $pro->phoneNumber = $request->phoneNumber ;
                $pro->save();
            }

            else
                return response()->json([
                    'message' => 'your profile updated successfully',
                    'profile' => $pro,
                    'skills'=>$skills,
                ]);
        }
        else
            return response()->json([
                'message' => ' you haven\'t profile to update it '
            ],200);

    }
    public function show_profile(){

        if(Profile::where('user_id', auth()->user()->id)->exists())
        {
            $pro = Profile::where('user_id', auth()->user()->id)->first();
            $skills=profileSkill::select('name')->where('Profile_id','=',$pro->id)->get();

            return response()->json([
                'profile'=>$pro,
                'skills'=>$skills,
                ],200);
        }
        else
            return response()->json([
                'message' => 'you haven\'t a profile',
            ], 400);

    }
    public function add_public_comment(Request $request){
        $validator = Validator::make($request->all(),[
            'id'       => 'required|int',
            'text'       => 'required|string',
        ]);
        if ($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
        if(public_post::where('id','=',$request->id)->exists())
        {
            $comment=public_comment::create([
                'user_id'=>auth()->user()->id,
                'public_post_id'=>$request->id,
                'text'=>$request->text
            ]);
            return response()->json([
                'message' => 'comment added successfully',
                'comment'=>$comment
            ], 200);
        }
        else
            return response()->json([
                'message' => 'your post not found',
            ], 403);
    }
    public function public_post_like(Request $request){
        $validator = Validator::make($request->all(),[
            'id'       => 'required|int',
        ]);
        if ($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
        if(public_post::where('id','=',$request->id)->exists())
        {
            if(public_like::where('public_post_id','=',$request->id)->where('user_id','=',auth()->user()->id)->exists())
            {
                public_like::where('public_post_id','=',$request->id)
                    ->where('user_id','=',auth()->user()->id)->delete();
                return response()->json([
                    'message' => 'you unliked the post',
                    'number of likes on post' => public_like::where('public_post_id', '=', $request->id)->count()
                ], 200);
            }
            else
            {
                public_like::create([
                    'user_id' => auth()->user()->id,
                    'public_post_id' => $request->id,
                ]);
                return response()->json([
                    'message' => 'you liked the post',
                    'likes' => public_like::where('public_post_id', '=', $request->id)->count()
                ], 200);
            }
        }
        else
            return response()->json([
                'message' => 'your post not found',
            ], 403);
    }
    public function campaign_post_like(Request $request){
        $validator = Validator::make($request->all(),[
            'id'       => 'required|int',
        ]);
        if ($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
        if(Campaign_Post::where('id','=',$request->id)->exists())
        {
            if(campaign_like::where('Campaign_Post_id','=',$request->id)->where('user_id','=',auth()->user()->id)->exists()) {
                campaign_like::where('Campaign_Post_id','=',$request->id)->where('user_id','=',auth()->user()->id)->delete();
                return response()->json([
                    'message' => 'you liked the post',
                    'likes' => campaign_like::where('Campaign_Post_id', '=', $request->id)->count()
                ], 200);
            }
            else
            {

                campaign_like::create([
                    'user_id' => auth()->user()->id,
                    'Campaign_Post_id' => $request->id,
                ]);
                return response()->json([
                    'message' => 'you liked the post',
                    'number of likes on post' => campaign_like::where('Campaign_Post_id', '=', $request->id)->count()
                ], 200);
            }
        }
        else
            return response()->json([
                'message' => 'your post not found',
            ], 403);
    }
    public function favorite_campaign(Request $request){
        $validator = Validator::make($request->all(),[
            'volunteer_campaign_id'       => 'required|int',
        ]);
        if ($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
        if(volunteer_campaign::where('id','=',$request->volunteer_campaign_id)->exists())
        {
            if(favorite::where('volunteer_campaign_id','=',$request->volunteer_campaign_id)->where('user_id','=',auth()->user()->id)->exists())
            {
                    favorite::where('volunteer_campaign_id', '=', $request->volunteer_campaign_id)->where('user_id', '=', auth()->user()->id)->delete();
                    return response()->json([
                        'message' => 'you delete the campaign from favorite',
                    ], 200);
            }
            else
            {
                favorite::create([
                    'user_id'=>auth()->user()->id,
                    'volunteer_campaign_id'=>$request->volunteer_campaign_id
                ]);
                return response()->json([
                    'message' => 'you added the campaign to your favorite',
                ], 200);
            }
        }
        else
            return response()->json([
                'message' => 'your campaign not found',
            ], 403);
    }
    public function get_favorite(){
        $fav=favorite::where('user_id', '=', auth()->user()->id)->with('volunteer_campaign')->get()->pluck('volunteer_campaign');
        return response()->json([
            'favorite' => $fav,
        ], 200);

    }
    public function add_rate(Request $request){
        $validator = Validator::make($request->all(),[
            'volunteer_campaign_id'       => 'required|int',
            'rate'       => 'required|int|between:1,5',
        ]);
        if ($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
        if(volunteer_campaign::where('id','=',$request->volunteer_campaign_id)->exists())
        {
            if(volunteer_campaign_rate::where('volunteer_campaign_id','=',$request->volunteer_campaign_id)->where('user_id','=',auth()->user()->id)->exists())
            {
                return response()->json([
                    'message' => 'you already add a rate',
                ], 403);
            }
            else
            {
                volunteer_campaign_rate::create([
                    'user_id'=>auth()->user()->id,
                    'volunteer_campaign_id'=>$request->volunteer_campaign_id,
                    'rate'=>$request->rate
                ]);
                return response()->json([
                    'message' => 'you added a rate',
                ], 200);
            }
        }
        else
            return response()->json([
                'message' => 'your campaign not found',
            ], 403);
    }
    public function update_rate(Request $request){
        $validator = Validator::make($request->all(),[
            'volunteer_campaign_id'       => 'required|int',
            'rate'       => 'required|int|between:1,5',
        ]);
        if ($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
        if(volunteer_campaign::where('id','=',$request->volunteer_campaign_id)->exists())
        {
            if(volunteer_campaign_rate::where('volunteer_campaign_id','=',$request->volunteer_campaign_id)->where('user_id','=',auth()->user()->id)->exists())
            {
                volunteer_campaign_rate::
                where('volunteer_campaign_id','=',$request->volunteer_campaign_id)
                    ->where('user_id','=',auth()->user()->id)
                    ->update(['rate'=>$request->rate]);
                return response()->json([
                    'message' => 'you update your rate',
                ], 200);
            }
            else
            {
                return response()->json([
                    'message' => 'you haven\'t rate to update it',
                ], 403);
            }
        }
        else
            return response()->json([
                'message' => 'your campaign not found',
            ], 403);
    }
    public function search_name(Request $request){
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string',
        ]);
        if ($validator->fails())
            return response()->json($validator->errors()->toJson(), 400);
        if(volunteer_campaign::where('name','like', '%'.$request->name.'%')->exists())
        {
            return response()->json([
                'campaign' => volunteer_campaign::where('name','like', '%'.$request->name.'%')->get(),
            ], 200);
        }
        else
            return response()->json([
                'message' => 'there is no campaign with this name',
            ], 403);
    }
    public function statistics_likes(){
        if(public_like::where('user_id','=',auth()->user()->id)->exists()) {
            return response()->json([
                'likes' => public_like::where('user_id', '=', auth()->user()->id)->count(),
            ], 200);
        }
        else
            return response()->json([
                'message' => 'you haven\'t any like ',
            ], 403);
    }
    public function statistics_accepted_requests(){
        $requests=volunteer_campaign_request::where('user_id','=',auth()->user()->id)->where('seen','=',true)->pluck('id');
        $campaigns=0;
        foreach ($requests as $camapain)
        {
            if(volunteer_campaign::where('volunteer_campaign_request_id','=',$camapain))
            {
                $campaigns++;
            }
        }
        if($campaigns==0)
        {
            return response()->json([
                'message' => 'you haven\'t any accepted campaign request',
            ], 403);
        }
        return response()->json([
            'accepted campaign request' => $campaigns,
        ], 200);

    }
    public function statistics_campaigns(){
        if(volunteer::where('user_id','=',auth()->user()->id)->exists())
        {
            $counter=volunteer::where('user_id','=',auth()->user()->id)
                ->count();
            $volunteers=volunteer::where('user_id','=',auth()->user()->id)
                ->get();
            $campaign = collect();
            foreach($volunteers as $volunteer)
            {

                $v_campaigns = volunteer_campaign::select('id', 'name', 'image', 'type', 'details', 'volunteer_number', 'current_volunteer_number')
                    ->where('id','=',$volunteer->volunteer_campaign_id)->first();

                    if (volunteer_campaign_rate::where('volunteer_campaign_id', '=', $v_campaigns->id)->exists())
                    {
                        $rating = collect();
                        $rates = volunteer_campaign_rate::where('volunteer_campaign_id', '=', $v_campaigns->id)->get();
                        foreach ($rates as $rate) {
                            $rating->push($rate->rate);
                        }
                        $rating = $rating->avg();
                        $campaign->push(['id' => $v_campaigns->id, 'name' => $v_campaigns->name,
                            'image' => $v_campaigns->image, 'type' => $v_campaigns->type,
                            'details' => $v_campaigns->details,
                            'volunteer_number' => $v_campaigns->volunteer_number,
                            'current_volunteer_number' => $v_campaigns->current_volunteer_number,
                            'rate' => $rating]);
                    }
                    else
                    {
                        $campaign->push(['id' => $v_campaigns->id, 'name' => $v_campaigns->name,
                            'image' => $v_campaigns->image, 'type' => $v_campaigns->type,
                            'details' => $v_campaigns->details,
                            'volunteer_number' => $v_campaigns->volunteer_number,
                            'current_volunteer_number' => $v_campaigns->current_volunteer_number,
                            'rate' => 0]);
                    }

            }
            return response()->json([
                'campaign number' => $counter,
                'campaigns' => $campaign
            ], 200);
        }
        else
            return response()->json([
                'message' => 'you arn\'t  member in any campaign',
            ], 403);
    }
    public function convert_points_request(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'points'     => 'required|int',
        ]);
        if ($validator->fails())
            return response()->json($validator->errors()->toJson(), 400);
        $point=point::where('user_id','=',auth()->user()->id)->first();
        if($point->value>=$request->points)
        {
            points_convert_request::create(['user_id'=>auth()->user()->id,
                    'value'=>$request->points
            ]);
            $point->update(['value'=>$point->value-$request->points]);
            return response()->json([
                'message' => 'convert request has been sent',
                'points'=>$point->value
            ], 200);
        }
        else
            return response()->json([
                'message' => 'Your points  less than your order',
                'points'=>$point->value
            ], 403);

    }
    public function campaign_suggestions()
    {
        if(volunteer::where('user_id','=',auth()->user()->id)->exists())
        {
            $campaigns=DB::table('volunteers')
                ->join('volunteer_campaigns',
                    'volunteers.volunteer_campaign_id','=','volunteer_campaigns.id')
                ->where('volunteers.user_id','=',auth()->user()->id)
                ->select('volunteer_campaigns.type')
            ->get();
            $human=0;
            $natural=0;
            $pets=0;
            $others=0;
            $max=collect();
            foreach ($campaigns as $campaign)
            {
                if($campaign->type=='human')
                {
                    $human++;
                }
                if($campaign->type=='natural')
                {
                    $natural++;
                }
                if($campaign->type=='pets')
                {
                    $pets++;
                }
                if($campaign->type=='others')
                {
                    $others++;
                }
            }
            $max->push(['human'=>$human,'natural'=>$natural,
                'pets'=>$pets,'others'=>$others,
            ]);
            return
            $max=max($max->human);



            return response()->json([
                $max,
                'campaigns' => $campaigns
            ], 200);
        }
        else
            return response()->json([
                'campaigns' => volunteer_campaign::orderBy('created_at', 'DESC')
                    ->get(),
            ], 200);
    }

}
