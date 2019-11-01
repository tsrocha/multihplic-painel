<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use GuzzleHttp\Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;

class ConfigurationsController extends Controller
{
    public $base_url;
    public $connection;
    public $client;
    public $user;

    public function __construct()
    {
        $this->base_url = 'http://api.multihplic.com.br/api/';
        $this->middleware(function ($request, $next) {
            $this->user = Session::get('user');
            return $next($request);
        });
    }

    public function themeAdd() {

        if($this->user->type != 'admin') {
            return redirect('/');
        }

        $data['title'] = 'Cadastro de temas';
        return View::make($this->user->type.'.temas-cadastro', $data);
    }

    public function themeStore(Request $request) {

        if($this->user->type != 'admin') {
            return redirect('/');
        }

        $body = $request->all();

        $images = [];
        if($request->hasFile('filename')) {

            $this->validate($request, ['filename' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048']);

            foreach ($request->file('filename') as $file){
                $name = md5(time()) . '.' .$file->getClientOriginalExtension();
                $filePath = 'themes/' . $request->get('slug') . '/' . $name;
                Storage::disk('s3')->put($filePath, file_get_contents($file), 'public');
                $url  = Storage::disk('s3')->url($filePath);
                $images[] .= $url;
            }

        }

        unset($body['_token']);
        unset($body['filename']);

        $body['images'] = $images;

        $client = new Client();
        $result = $client->request('POST', $this->base_url.'theme/add', [
            'form_params' => $body,
            'headers' => [
                'Authorization' => 'Bearer '.Session::get('MultihplicAuth')
            ]
        ]);

        $response = json_decode($result->getBody()->getContents());

        if(isset($response->status) && $response->status == 'Token is Expired') {
            return redirect('/logout');
        }

        if(isset($response->return) && $response->return == 'success') {
            $data['return'] = ['status' => 'success', 'msg' => 'Cadastro efetuado com sucesso!'];
        } else {
            $data['return'] = ['status' => 'error', 'msg' => $response->return->msg];
        }

        $data['title'] = 'Cadastro de temas';
        $data['return'] = $data['return'];

        return View::make($this->user->type.'.temas-cadastro', $data);

    }

    public function themeList() {

        if($this->user->type == 'provider') {
            $data['company_id'] = $this->user->provider[0]->company_id;
        }

        $client = new Client();
        $result = $client->request('GET', $this->base_url.'theme/list', [
            'headers' => [
                'Authorization' => 'Bearer '.Session::get('MultihplicAuth')
            ]
        ]);

        $response = json_decode($result->getBody()->getContents());
        $themes = json_decode($result->getBody(), true);

        if(isset($response->status) && $response->status == 'Token is Expired') {
            return redirect('/logout');
        }

        if(isset($response->return) && $response->return == 'error') {
            $data['return'] = ['status' => 'error', 'msg' => $response->return->msg];
        }


        $data['title'] = 'Lista de temas';
        $data['themes'] = $themes;
        $data['userType'] = $this->user->type;
        return View::make($this->user->type.'.temas-lista', $data);
    }

    public function themeUp($id) {
        if($this->user->type != 'provider') {
            return redirect('/');
        }

        if($this->user->type == 'provider') {
           $companyId = $this->user->provider[0]->company_id;
        }

        $data = ['provider_id' => $companyId, 'theme_id' => $id];

        $client = new Client();
        $result = $client->request('POST', $this->base_url.'theme/upprovider', [
            'form_params' => $data,
            'headers' => [
                'Authorization' => 'Bearer '.Session::get('MultihplicAuth')
            ]
        ]);

        $response = json_decode($result->getBody()->getContents());

        if(isset($response->status) && $response->status == 'Token is Expired') {
            return redirect('/logout');
        }

        if(isset($response->return) && $response->return == 'error') {
            $data['return'] = ['status' => 'error', 'msg' => $response->return->msg];
        }

        return redirect('/provider/temas');

    }

    public function themeDel($id) {

        if($this->user->type != 'admin') {
            return redirect('/');
        }

        $client = new Client();
        $result = $client->request('GET', $this->base_url.'theme/del/'.$id, [
            'headers' => [
                'Authorization' => 'Bearer '.Session::get('MultihplicAuth')
            ]
        ]);

        $response = json_decode($result->getBody()->getContents());

        if(isset($response->status) && $response->status == 'Token is Expired') {
            return redirect('/logout');
        }

        return redirect('/admin/temas');

    }

}
