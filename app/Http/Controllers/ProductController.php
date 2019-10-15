<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use GuzzleHttp\Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use App\Help;

class ProductController extends Controller
{
    public $base_url;
    public $connection;
    public $client;
    public $user;
    public $url_image;

    public function __construct()
    {
        $this->base_url = 'http://api.multihplic.com.br/api/';
        $this->url_image = '//painel.multihplic.com.br/images/products/';
        $this->middleware(function ($request, $next) {
            $this->user = Session::get('user');
            return $next($request);
        });
    }

    public function list() {

        $this->client = new Client();
        $result = $this->client->request('GET', $this->base_url.'provider/product/list/'.$this->user->id, [
            'headers' => [
                'Authorization' => 'Bearer '.Session::get('MultihplicAuth')
            ]
        ]);

        $resultDepartaments = $this->client->request('GET', $this->base_url.$this->user->type.'/product/departament/list/'.$this->user->id, [
            'headers' => [
                'Authorization' => 'Bearer '.Session::get('MultihplicAuth')
            ]
        ]);

        $resultBrands = $this->client->request('GET', $this->base_url.$this->user->type.'/product/brand/list/'.$this->user->id, [
            'headers' => [
                'Authorization' => 'Bearer '.Session::get('MultihplicAuth')
            ]
        ]);

        $resultAtribute = $this->client->request('GET', $this->base_url.$this->user->type.'/product/atribute/list/'.$this->user->id.'/'.$this->user->type, [
            'headers' => [
                'Authorization' => 'Bearer '.Session::get('MultihplicAuth')
            ]
        ]);

        $atributes = (array) json_decode($resultAtribute->getBody());
        $brands = (array) json_decode($resultBrands->getBody());
        $departaments = (array) json_decode($resultDepartaments->getBody());
        $products = (array) json_decode($result->getBody());

        if(isset($products['status']) && $products['status'] == 'Token is Expired') {
            return redirect('/logout');
        }

        $data['departaments'] = $departaments;
        $data['atributes'] = $atributes;
        $data['brands'] = $brands;
        $data['products'] = $products;
        $data['title'] = 'Atributos e variações';
        return View::make($this->user->type.'.produtos-lista', $data);
    }

    public function add() {

        $this->client = new Client();
        $result = $this->client->request('GET', $this->base_url.$this->user->type.'/product/departament/list/'.$this->user->id, [
            'headers' => [
                'Authorization' => 'Bearer '.Session::get('MultihplicAuth')
            ]
        ]);

        $read = (array) json_decode($result->getBody());

        if(isset($read['status']) && $read['status'] == 'Token is Expired') {
            return redirect('/logout');
        }

        $data['read'] = $read;
        $data['title'] = 'Atributos e variações';
        return View::make($this->user->type.'.produtos-cadastro', $data);
    }

    public function store(Request $request) {

        $body = $request->all();

        unset($body['_token']);

        $this->client = new Client();
        $result = $this->client->request('POST', $this->base_url.'provider/product/add/'.$this->user->id, [
            'form_params' => $body,
            'headers' => [
                'Authorization' => 'Bearer '.Session::get('MultihplicAuth')
            ]
        ]);

        $response = json_decode($result->getBody()->getContents());

        if(isset($response->status) && $response->status == 'Token is Expired') {
            return redirect('/logout');
        }

        if(isset($response->id)) {
            $data['return'] = ['status' => 'success', 'msg' => 'Cadastro efetuado com sucesso!'];
        } else {
            $data['return'] = ['status' => 'error', 'msg' => 'Erro ao tentar cadastrar categorias.'];
        }

        return redirect('/provider/product/images/'.$response->id);
    }

    public function images($id) {

        $this->client = new Client();
        $result = $this->client->request('GET', $this->base_url.$this->user->type.'/product/images/'.$id, [
            'headers' => [
                'Authorization' => 'Bearer '.Session::get('MultihplicAuth')
            ]
        ]);

        $images = (array) json_decode($result->getBody());

        if(isset($brands['status']) && $images['status'] == 'Token is Expired') {
            return redirect('/logout');
        }

        $data['images'] = $images;
        $data['id'] = $id;
        $data['title'] = 'Imagens de produto';
        return View::make($this->user->type.'.produtos-imagens', $data);
    }

    public function upload(Request $request, $id) {

        $body = [];
        $body['image'] = [];

        if($this->user->type == 'provider') {
            $socialName = $this->user->provider[0]->company[0]->socialName;
        } else if($this->user->type == 'shop') {
            $socialName = $this->user->shop[0]->company[0]->socialName;
        } else {
            return redirect('/');
        }

        if($request->hasFile('images')) {

            foreach ($request->file('images') as $file){

                $name = md5(time()) . '.' .$file->getClientOriginalExtension();
                $filePath =  Help::slug($socialName).'products/' . $name;
                Storage::disk('s3')->put($filePath, file_get_contents($file), 'public');
                $url  = Storage::disk('s3')->url($filePath);
                $body['image'][] .= $url;
            }

        }

        $this->client = new Client();
        $result = $this->client->request('POST', $this->base_url.'provider/product/images/add/'.$id, [
            'form_params' => $body,
            'headers' => [
                'Authorization' => 'Bearer '.Session::get('MultihplicAuth')
            ]
        ]);

        $response = json_decode($result->getBody()->getContents());

        if(isset($response->status) && $response->status == 'Token is Expired') {
            return redirect('/logout');
        }

        return redirect('/provider/product/images/'.$id);
    }

    public function deleteImages($product_id, $id) {

        $this->client = new Client();
        $result = $this->client->request('DELETE', $this->base_url.'provider/product/image/delete/'.$id, [
            'headers' => [
                'Authorization' => 'Bearer '.Session::get('MultihplicAuth')
            ]
        ]);

        return redirect('/provider/product/images/'.$product_id);
    }


    public function delete($id) {

        $this->client = new Client();
        $result = $this->client->request('DELETE', $this->base_url.'provider/product/delete/'.$id, [
            'headers' => [
                'Authorization' => 'Bearer '.Session::get('MultihplicAuth')
            ]
        ]);

        return redirect('/provider/product/list');
    }

}
