<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Redirect;

// MODELS
use App\Models\Action;

class ActionController extends Controller
{
    protected $controllers, $calls;
    /**
     *  if user not login redirect to login page
     */
    public function __construct()
    {
        $this->middleware('admin', [
            'only' => ['index', 'create', 'store', 'update', 'destroy', 'edit', 'show']
        ]);

        $this->controllers = include('action/controllers.php');
        $this->calls = array_unique(['' => 'Please select ..', '1' => 'Internal', '0' => 'External']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $actions = Action::latest()->paginate(20);
        return view('action.index', compact('actions'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $controllers = array_unique($this->controllers);
        $calls       = $this->calls;
        return view('action.create', compact('controllers', 'calls'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'controller' =>  'required',
            'method1'    =>  'required|unique:action,method', //this was variable name was changed to method1 due to some problem while inserting in db
            'action'     =>  'required|unique:action,action',
            'call'       =>  'required'
        ]);

        $data = ['controller' => $request->controller,'method'=>$request->method1,'action'=>$request->action, 'call'=>$request->call];
        Action::create($data);

        return redirect('actions')->with('flash_message', 'Action added successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $action = Action::findorfail($id);

        return view('action.show', compact('action'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $controllers = array_unique($this->controllers);
        $calls       = $this->calls;
        $action = Action::where('id', $id)->first();

        return view('action.edit', compact('controllers', 'action', 'calls'));
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
            'controller' => 'required',
            'method'     => 'required|unique:action,method,'.$id
        ]);

        $action = Action::findorfail($id);
        $action->update($request->all());

        return redirect('/actions')->with('flash_message', 'Action updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $action = Action::find($id);
        $action->delete();
        return Redirect::back()->with('flash_message', 'Action deleted successfully');
    }
}