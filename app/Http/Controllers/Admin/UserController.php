<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Logs;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\StoreUser;
use App\Http\Requests\UpdateUser;
use Illuminate\Validation\Rule;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
	 
	public function __construct()
    {
        $this->middleware('auth');
    }
	
    public function index()
    {
        $search = request('search');

        if (!empty($search)) {
            $users = User::where('users.name', 'like', '%'.$search.'%')
                ->orWhere('users.email', 'like', '%'.$search.'%')
                ->orderBy('users.id','DESC')
                ->paginate(10);
        } else {
            $users = User::orderBy('users.id','DESC')->paginate(15);
        }

        return view('users.index', compact('users') );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('users.add');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUser $request)
    {

        $data = [
            'name'  => $request->name,
            'email'  => $request->email,
            'password' => Hash::make($request->password),
        ];

        $record = User::create( $data );
        Logs::add_log(User::getTableName(), $record->id, $data, 'add', '');

        return redirect()->route('users.index')->with('success','Record Added !');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $record = User::whereId($id)->first();

        $logs = Logs::get_logs_details(User::getTableName(), $id);

        if($record != false){
            return view('users.edit', compact('record','logs'));
        }else{
            abort(404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $this->validate($request, [
            'name' => 'required',
            'email' => [
                'required',
                Rule::unique('users')->ignore($id),
            ]
        ]);

        $user = User::find($id);
        $input = $request->all();

        if($request->password == null || $request->password == NULL || $request->password == ''){
            $input = $request->except(['password']);
        }else{
            $input['password'] = Hash::make($input['password']);
        }

        $user->update($input);

        Logs::add_log(User::getTableName(), $id, $input, 'edit', 1);
        return redirect()->route('users.index')->with('success','Record Updated !');


    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);
        $user->delete();
 
        return redirect()->route('users.index')->with('success', 'Record Deleted !');
    }
}
