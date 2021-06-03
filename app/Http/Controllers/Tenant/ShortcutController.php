<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Shortcut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShortcutController extends Controller{
    //批量删除快捷回复
    public function delManyShortcut(Request $request){
        Shortcut::destroy($request->all());
        return $this->jsonRertun(200,'删除成功','删除成功');
    }
    //上传Xsl传过来的文件,入快捷发送库
    public function uploadXsl(Request $request){
        $date=date('Y-m-d H:i:s',time());
        $data=[];
        $lastId=Shortcut::orderBy('id','desc')->first();
        if(empty($lastId)){
            $lastId=0;
        }else{
            $lastId=$lastId->id;
        }
        $user=auth()->user();
        /*categoryName: undefined
        content: "我正在解决"
        numbering: "C"
        title: "客户的问题"*/
        foreach ($request->all() as $v){
            if(empty($v['numbering'])){
                return $this->jsonRertun(402,'格式不对','格式不对');
            }
            if(!empty($v['categoryName'])){
                $a['created_at']=$date;
                $a['father_id']=0;
                $a['content']=$v['categoryName'];
                $a['title']=$v['categoryName'];
                $a['sort']=999;
                $a['id']=++$lastId;
                $a['user_id']=$user->id;
                $data[]=$a;
                foreach($request->all() as $value){
                    if($value['numbering']==$v['numbering'] && empty($value['categoryName'])){
                        $b['created_at']=$date;
                        $b['father_id']=$a['id'];
                        $b['content']=$value['content'];
                        $b['title']=$value['title'];
                        $b['sort']=999;
                        $b['id']=++$lastId;
                        $b['user_id']=$user->id;
                        $data[]=$b;
                    }
                }
            }
        }
        Shortcut::insert($data);
        return $this->jsonRertun(200,'上传成功',$data);
    }
    public function index(){
        //
        $datas=Shortcut::where('user_id',auth()->user()->id)->orderBy('sort','desc')->orderBy('created_at','desc')->get()->toArray();
        $data=$this->make_tree($datas);
        return $this->jsonRertun(200,'得到快捷语成功',$data);
    }
    public function create(){
        //
    }
    public function store(Request $request){
        //
        $validator=Validator::make($request->all(),[
            'sort'=>'required',
            'title'=>'required'
        ]);
        if($validator->fails()) {
            return response()->json(['error'=>$validator->errors()]);
        }
        if($request->father_id){
            $content=$request->input('content');
        }else{
            $content=$request->title;
        }
        $id=Shortcut::insertGetId([
            'father_id'=>$request->father_id,
            'user_id'=>auth()->user()->id,
            'sort'=>$request->sort,
            'title'=>$request->title,
            'content'=>$content,
            'created_at'=>date('Y-m-d H:i:s',time())
        ]);
        $data=$request->all();
        $data['content']=$content;
        $data['id']=$id;
        return $this->jsonRertun(200,'添加快捷回复成功',$data);
    }
    public function show($id){
        //
    }
    public function edit($id){
        //
    }
    public function update(Request $request, $id){
        //
        $validator=Validator::make($request->all(),[
            'sort'=>'required',
            'title'=>'required'
        ]);
        if($validator->fails()) {
            return response()->json(['error'=>$validator->errors()]);
        }
        if($request->father_id){
            $content=$request->input('content');
        }else{
            $content=$request->title;
        }
        Shortcut::where('id',$id)->update([
            'father_id'=>$request->father_id,
            'sort'=>$request->sort,
            'title'=>$request->title,
            'content'=>$content
        ]);
        return $this->jsonRertun(200,'修改快捷回复成功','修改快捷回复成功');
    }
    public function destroy($id){
        //
        Shortcut::where('id',$id)->delete();
        return $this->jsonRertun(200,'删除快捷回复成功','删除快捷回复成功');
    }
}
