<?php
namespace NexusPlugin\IyuuPushTorrent\Http\Controllers;

use App\Http\Controllers\Controller;
use NexusPlugin\IyuuPushTorrent\Repositories\SearchhashRespones;
use Illuminate\Http\Request;
use NexusPlugin\IyuuPushTorrent\Repositories\Bencode;

class IyuuPushTorrentController extends Controller
{
    private $repository;

    public function __construct(SearchhashRespones $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $rules = [
            'torrent_id' => 'required|int',
        ];
        $request->validate($rules);
        try{
            $rep = new Bencode();
            $resource = $rep->reseed($request->torrent_id);
            $hash = $resource['info_hash'];
            return $this->success($this->repository->searchhash($hash));
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $data =['请求异常'.$msg];
            return fail($msg, $data);
        }

    }
}