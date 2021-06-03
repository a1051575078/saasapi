<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Log;
use App\Models\Tenant\User;
use Hyn\Tenancy\Models\Hostname;
use Illuminate\Http\Request;

class LogController extends Controller{
    public function index(){
        //
        $data=Log::with('user')->orderBy('id','desc')->get();
        return $this->jsonRertun(200,'查看日志成功',$data);
    }
    public function create(){
        //
    }
    public function store(Request $request){
        //
    }
    public function show($id){
        //
    }
    public function edit($id){
        //
    }
    public function update(Request $request, $id){
        //
    }
    public function destroy($id){
        //
    }
}
