<?php

namespace App\Http\Controllers;

use App\Models\campaignSkill;
use App\Models\ChatRoom;
use App\Models\donation_campaign_request;
use App\Models\Profile;
use App\Models\public_post;
use App\Models\user_role;
use App\Models\volunteer;
use App\Models\volunteer_campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB ;
use App\Models\volunteer_campaign_request;


use App\Models\location;
class AdminController extends Controller
{
    public function all_volunteer_campaign_request()
    {
        if(volunteer_campaign_request::exists()) {
            $campaign=DB::table('volunteer_campaign_requests')
            ->select('profiles.name as user name','profiles.image as user image','volunteer_campaign_requests.*')
            ->join('profiles','volunteer_campaign_requests.user_id','=','profiles.user_id')
            ->get();
            return response()->json([
                'message' => 'campaign added successfully',
                'requests' => $campaign
            ], 200);
        }
        else
            return response()->json([
                'message' => 'no any request',
            ], 400);
    }

    public function all_donation_campaign_request(Request $request)
    {
        if(donation_campaign_request::where('seenAndAccept', '=', false)->exists())
        {
            $campaign=DB::table('donation_campaign_requests')
                ->select('profiles.name as user name','profiles.image as user image','donation_campaign_requests.*')
                ->join('profiles','donation_campaign_requests.user_id','=','profiles.user_id')
                ->where('donation_campaign_requests.seenAndAccept', '=', false)
                ->get();
        return response()->json([
            'message' => 'campaign added successfully',
            'requests' => $campaign
        ], 200);
        }
         else
        return response()->json([
            'message' => 'no any request',
        ], 400);
    }

    public function acceptAndUnanswered(Request $request)
    {
        if(donation_campaign_request::where('seenAndAccept', '=', false)->exists()||volunteer_campaign_request::exists()
        ||volunteer_campaign::exists())
        {
            $dcampaigns = donation_campaign_request::where('seenAndAccept', '=', true)->get();
            $dcampaign = donation_campaign_request::where('seenAndAccept', '=', false)->get();
            $vcampaign=volunteer_campaign::get();
            $vcampaigns=volunteer_campaign_request::get();
            return response()->json([
                'accepted donation campaign' => $dcampaigns->count(),
                'unanswered donation campaign' => $dcampaign->count(),
                'accepted volunteer campaign' => $vcampaign->count(),
                'unanswered volunteer campaign' => $vcampaigns->count(),
            ], 200);
        }
        else
            return response()->json([
                'message' => 'no any request',
            ], 400);
    }

    public function all_user_leader_in_future()
    {

            if(profile::where('leaderInFuture','=',true)->exists()) {
                $profiles=profile::where('leaderInFuture','=',true)->get();
                return response()->json([
                    'message' => 'profiles for user who want to be leader in future',
                    'profiles' => $profiles
                ], 400);
            }
            else
                return response()->json([
                    'message' => 'no any user want to be leader',
                ], 400);
    }

    public function response_on_volunteer_campaign_request(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|int',
            'accept' => 'required|boolean',
            'leader_id' => 'required_if:accept,==,true|int',
            'age' => 'required_if:accept,==,true|int',
            'study' => 'required_if:accept,==,true|string',
            'skills' => 'required_if:accept,==,true',
        ]);
        if ($validator->fails())
            return response()->json($validator->errors()->toJson(), 400);


        if(volunteer_campaign_request::where('id', '=', $request->id)->exists()) {
            $campaign_request = volunteer_campaign_request::where('id', '=', $request->id)->first();
            if ($request->accept==true) {

                $campaign = new volunteer_campaign();

                $campaign->volunteer_campaign_request_id = $campaign_request->id;
                $campaign->location_id = $campaign_request->location_id;
                $campaign->image = $campaign_request->image;
                $campaign->details = $campaign_request->details;
                $campaign->type = $campaign_request->type;
                $campaign->name = $campaign_request->name;
                $campaign->volunteer_number = $campaign_request->volunteer_number;
                $campaign->longitude = $campaign_request->longitude;
                $campaign->latitude = $campaign_request->latitude;
                $campaign->maxDate = $campaign_request->maxDate;
                $campaign->leader_id = $request->leader_id;
                $campaign->age = $request->age;
                $campaign->study = $request->study;
                $campaign->save();
                foreach($request->skills as $skill)
                {
                    campaignSkill::create(['name'=>$skill,'volunteer_campaign_id'=>$campaign->id]);
                }


                $skills=campaignSkill::select('name')->where('volunteer_campaign_id','=',$campaign->id)->get();


                $group = new ChatRoom();
                $group->name = $campaign->name;
                $group->volunteer_campaign_id = $campaign->id;
                $group->save();

                $pro=Profile::select('user_id')->where('id','=',$request->leader_id)->first();
                $id=$pro->user_id;
                user_role::create(['user_id'=> $id,'role_id'=> 3],
                    ['user_id'=> $id, 'role_id'=> 4]);

                $volunteer = new volunteer;
                $volunteer->user_id = $id;
                $volunteer->volunteer_campaign_id = $campaign->id;
                $volunteer->is_leader = true;
                $volunteer->save();

                $campaign_request->delete();

                return response()->json([
                    'message' => 'campaign added successfully',
                    'campaign' => $campaign,
                    'skills'=>$skills
                ], 200);
            } else {
                $campaign_request->delete();
                return response()->json([
                    'message' => 'request deleted',
                ], 200);
            }
        }
        else
            return response()->json([
                'message' => 'This request does not exist',
            ], 400);
    }


    public function response_on_donation_campaign_request(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|int',
            'accept' => 'required|boolean'
        ]);
        if ($validator->fails())
            return response()->json($validator->errors()->toJson(), 400);

        if(donation_campaign_request::where('id', '=', $request->id)->exists())
        {
        $campaign_request = donation_campaign_request::where('id', '=', $request->id)->first();
            if ($request->accept==true) {
                $campaign_request->seenAndAccept = true;
                $campaign_request->save();
                return response()->json([
                    'message' => 'campaign added successfully',
                ], 200);
            } else {
                $campaign_request->delete();
                return response()->json([
                    'message' => 'request deleted',
                ], 200);
            }
        }
        else
            return response()->json([
                'message' => 'This request does not exist',
            ], 400);
    }

    public function add_public_post(Request $request){
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'body'  => 'required|string' ,
            'image' => 'required'
        ]);
        if ($validator->fails())
            return response()->json($validator->errors()->toJson(), 400);

        //image
        $image = $request->file('image');
        $image_name = time() . '.' . $image->getClientOriginalExtension();
        $image->move('images', $image_name);

        $new_post = new public_post();
        $new_post->title = $request->title ;
        $new_post->body  = $request->body ;
        $new_post->image =$image_name ;
        $new_post->save();

        return response()->json([
            'post'    => $new_post,
            'message' => 'Post added Successfully'
        ],200);
    }//end

    public function update_public_Posts(Request $request){
        $validator=Validator::make($request->all(),[
            'id' => 'required'
        ]);

        if ($validator->fails())
            return response()->json($validator->errors()->toJson(), 400);

        $post_id = public_post::find($request->id);

        if(!$post_id)
            return response()->json([
                'message' => 'Post you have requested not found !'
            ]);

        $title = $request->title ;
        $body  = $request->body ;
        $photo = $request->photo;

        if(is_null($title) And is_null($body) And is_null($photo)){
            return response()->json([
                'message' => 'Enter an information to update !',
                'post is' => $post_id ,

            ]);
        }
        if(! is_null($title)){
            $post_id->title = $title;
        }
        if(! is_null($body)){
            $post_id->body = $body;
        }
        if(! is_null($photo)){
            $post_id->photo = $photo;
        }
        $post_id->save();
        return response()->json([
            'update post' => $post_id ,
            'message' => 'post updated successfully'
        ],200);

    }///end

    public function delete_public_post(Request $request){
        $validator=Validator::make($request->all(),[
            'id' => 'required'
        ]);

        if ($validator->fails())
            return response()->json([
                'message' => 'enter num of post you want to delete it !',
                $validator->errors()
            ],400 );

        $post_id = public_post::find($request->id);
        if(!$post_id)
            return response()->json([
                'message' => 'Post you have requested not found !'
            ]);

        $post_id->delete();
        return response()->json([
            'message' => 'Post deleted successfully !'
        ],200);
    }//end


    public function add_volunteer_campaign(Request $request){
        $validator = Validator::make($request->all(), [
            'name'    => 'required|string',
            'type'    => 'required|string',
            'details' => 'required|string|min:5',
            'maxDate' => 'required|date',
            'volunteer_number' => 'required|int',
            'volunteer_campaign_request_id' => 'required|int',
            'image'   => 'required',
            'leader_id'   => 'required|int',
            'city'   => 'required|string',
            'country'   => 'required|string',
            'street'   => 'required|string',
            'age'   => 'required|string',
            'study'   => 'required|string',
            'skills'   => 'required|array',

        ]);

        if ($validator->fails())
            return response()->json($validator->errors()->toJson(), 400);

        //image
        $image = $request->file('image');
        $image_name = time() . '.' . $image->getClientOriginalExtension();
        $image->move('images', $image_name);

        $location=new location();
        $location->country = $request->country;
        $location->city    = $request->city;
        $location->street  = $request->street;
        $location->save();

        $new_campaign = new volunteer_campaign();

        $new_campaign->name      = $request->name;
        $new_campaign->type      = $request->type;
        $new_campaign->details   = $request->details;
        $new_campaign->maxDate   = $request->maxDate;
        $new_campaign->volunteer_number = $request->volunteer_number;
        $new_campaign->location_id  = $location->id;
        $new_campaign->image     = $image_name;
        $new_campaign->leader_id = $request->leader_id;
        $new_campaign->volunteer_campaign_request_id=$request->volunteer_campaign_request_id;
        $new_campaign->longitude=$request->longitude;
        $new_campaign->latitude=$request->latitude;
        $new_campaign->age=$request->age;
        $new_campaign->study=$request->study;
        $new_campaign->save() ;

        foreach($request->skills as $skill)
        {
            campaignSkill::create(['name'=>$skill,'volunteer_campaign_id'=>$new_campaign->id]);
        }

        $skills=campaignSkill::select('name')->where('volunteer_campaign_id','=',$new_campaign->id)->get();

        $group = new ChatRoom();
        $group->name = $new_campaign->name;
        $group->volunteer_campaign_id = $new_campaign->id;
        $group->save();

        $pro=Profile::select('user_id')->where('id','=',$request->leader_id)->first();
        $id=$pro->user_id;
        user_role::create(['user_id'=> $id,'role_id'=> 3],
            ['user_id'=> $id, 'role_id'=> 4]);

        $volunteer = new volunteer;
        $volunteer->user_id = $id;
        $volunteer->volunteer_campaign_id = $new_campaign->id;
        $volunteer->is_leader = true;
        $volunteer->save();
        return response()->json([
            'message'  => 'campaign added Successfully',
            'campaign' => $new_campaign,
            'skills'=>$skills
        ],200);
    }

    public function update_volunteer_campaign(Request $request){
        $validator=Validator::make($request->all(),[
            'id' => 'required|int',
        ]);

        if ($validator->fails())
            return response()->json($validator->errors()->toJson(), 400);

        $campaign = volunteer_campaign::find($request->id);
        if( ! $campaign) {
            return response()->json([
                'message' => 'campaign you have requested not found'
            ]);
        }

        $name    = $request->name;
        $type    = $request->type;
        $details = $request->details;
        $target  = $request->target;
        $maxDate = $request->maxDate;
        $volunteer_number = $request->volunteer_number;
        $image   = $request->image;
        $leader_id   = $request->leader_id;
        $location_id   = $request->location_id;
        $latitude   = $request->latitude;
        $longitude   = $request->longitude;
        $study   = $request->study;
        $age   = $request->age;
        $skills=$request->skills;

        if(is_null($name) And is_null($type) And is_null($details) And is_null($target) And
            is_null($maxDate) And is_null($volunteer_number) And is_null($image)
            And is_null($leader_id) And is_null($location_id)And is_null($latitude)
            And is_null($longitude)And is_null($study)And is_null($age)
            And is_null($skills)
        ){
            return response()->json([
                'message' => 'Enter an information to update !',
                'campaign is' => $campaign
            ]);
        }

        if (!is_null($name)){
            $campaign->name = $name;
        }
        if (!is_null($type)){
            $campaign->type = $type;
        }
        if (!is_null($details)){
            $campaign->details = $details;
        }
        if (!is_null($target)){
            $campaign->target = $target;
        }
        if (!is_null($maxDate)){
            $campaign->maxDate = $maxDate;
        }
        if (!is_null($volunteer_number)){
            $campaign->volunteer_number = $volunteer_number;
        }
        if (!is_null($image)){
            $campaign->image = $image;
        }
        if (!is_null($leader_id)){
            $campaign->leader_id = $leader_id;
        }
        if (!is_null($study)){
            $campaign->study = $study;
        }
        if (!is_null($age)){
            $campaign->age = $age;
        }
        if (!is_null($location_id)){
            $loc_id=$campaign->location_id;
            $location = location::where('id','=',$loc_id)->first;
            $location->country = $request->country;
            $location->city    = $request->city;
            $location->street  = $request->street;
            $location->save();

        }
        if (!is_null($latitude)){
            $campaign->latitude = $latitude;
        }
        if (!is_null($longitude)){
            $campaign->longitude = $longitude;
        }
        $campaign->save();
        if(!is_null($request->skills))
        {
            campaignSkill::where('volunteer_campaign_id', '=', $campaign->id)->delete();
            foreach ($request->skills as $skill) {
                campaignSkill::create(['name' => $skill, 'volunteer_campaign_id' => $campaign->id]);
            }
            $skills=campaignSkill::select('name')->where('volunteer_campaign_id','=',$campaign->id)->get();
        }
        return response()->json([
            'message' => 'Campaign updated Successfully !',
            'update campaign ' => $campaign,
            'skills'=>$skills
        ]);
    }


    public function delete_volunteer_campaign(Request $request){
        $validator=Validator::make($request->all(),[
            'id' => 'required'
        ]);

        if ($validator->fails())
            return response()->json([
                'message' => 'enter num of campaign you want to delete it !',
                $validator->errors()
            ],400 );

        $campaign = volunteer_campaign::find($request->id);
        if(!$campaign)
            return response()->json([
                'message' => 'campaign you have requested not found !'
            ]);
        campaignSkill::where('volunteer_campaign_id', '=', $campaign->id)->delete();
        $campaign->delete();

        return response()->json([
            'message' => 'campaign deleted successfully !'
        ],200);
    }





}
