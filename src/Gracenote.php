<?php
namespace Atomescrochus\Gracenote;

use Atomescrochus\Gracenote\Exceptions\RequiredConfigMissing;
use Illuminate\Support\Facades\Cache;

class Gracenote
{
    private $client_id;
    private $client_tag;
    private $user_id;
    private $request_url;

    public $query_cmd;
    public $lang;
    public $search_type;
    public $search_terms;
    public $cache;

    public function __construct()
    {
        $this->setParameters();

        $this->lang = "eng";
        $this->search_terms = "";
        $this->query_cmd = "album_search"; // curently only possible option.
        $this->search_type = "TRACK_TITLE";
    }

    /**
     * Sets the time in minutes to cache the search results
     * @param  integer $cache A number of minutes
     */
    public function cache(int $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * Set the "prefered natural language of metadata"
     * @param  string $type One of the search type as defined by Gracenote API docs.
     */
    public function searchType($type)
    {
        $this->search_type = $type;
        return $this;
    }

    /**
     * Set the "prefered natural language of metadata"
     * @param  string $lang A three-character language code as defined by ISO 639-2
     */
    public function lang($lang)
    {
        $this->lang = $lang;
        return $this;
    }

    /**
     * Set the query to send to Gracenote
     * @param  string $query The search query
     */
    public function query($query)
    {
        $this->search_terms = $query;
        return $this;
    }

    /**
     * Sends the search
     * @return collection A collection of results
     */
    public function search()
    {
        $results = Cache::remember("{$this->search_type}-{$this->search_terms}", $this->cache, function () {
            return $this->searchGracenote();
        });

        return $results;
    }

    /**
     * Send a request for search to Gracenote WebAPI
     * @return collection A collection of results
     */
    private function searchGracenote()
    {
        $response = \Httpful\Request::post($this->request_url)
        ->body($this->xmlPayload())
        ->sendsXml()
        ->send();

        return $this->formatApiResults($response);
    }

    private function formatApiResults($results)
    {
        $raw = $results->raw_body;

        if ($this->search_type == "track_title") {
            $albums = $this->formatSearchTrackTitle($results->body->RESPONSE[0]->ALBUM);
        }

        if ($this->search_type == "album_title") {
            $albums = $this->formatSearchAlbumTitle($results->body->RESPONSE[0]->ALBUM);
        }

        if ($this->search_type == "artist") {
            $albums = $this->formatSearchArtist($results->body->RESPONSE[0]->ALBUM);
        }

        return (object) [
            'results' => $albums,
            'raw' => json_decode($raw),
        ];
    }

    private function formatSearchArtist($raw_albums)
    {
        return collect($raw_albums)->map(function ($item, $key) {
            $formatted = (object) [];
           
            if (isset($item->GN_ID)) {
                $formatted->gracenote_album_id = $item->GN_ID;
            }

            if (isset($item->TITLE[0]->VALUE)) {
                $formatted->album_title = $item->TITLE[0]->VALUE;
            }

            if (isset($item->ARTIST[0]->VALUE)) {
                $formatted->album_artist = $item->ARTIST[0]->VALUE;
            }

            if (isset($item->GENRE[0]->VALUE)) {
                $formatted->album_genre = $item->GENRE[0]->VALUE;
            }

            if (isset($item->DATE[0]->VALUE)) {
                $formatted->album_year = $item->DATE[0]->VALUE;
            }

            if (isset($item->TRACK_COUNT)) {
                $formatted->track_count = $item->TRACK_COUNT;
            }

            if (isset($item->TRACK)) {
                $formatted->tracks = collect($item->TRACK)->map(function ($item, $key) {
                    $formatted = (object) [];
                    if (isset($item->TRACK_NUM)) {
                        $formatted->track_number = $item->TRACK_NUM;
                    }

                    if (isset($item->GN_ID)) {
                        $formatted->gracenote_track_id = $item->GN_ID;
                    }

                    if (isset($item->ARTIST[0]->VALUE)) {
                        $formatted->artist = $item->ARTIST[0]->VALUE;
                    }

                    if (isset($item->TITLE[0]->VALUE)) {
                        $formatted->title = $item->TITLE[0]->VALUE;
                    }

                    return $formatted;
                })->toArray();
            }

            return $formatted;
        });
    }

    private function formatSearchAlbumTitle($raw_albums)
    {
        return collect($raw_albums)->map(function ($item, $key) {
            $formatted = (object) [];
           
            if (isset($item->GN_ID)) {
                $formatted->gracenote_album_id = $item->GN_ID;
            }

            if (isset($item->TITLE[0]->VALUE)) {
                $formatted->album_title = $item->TITLE[0]->VALUE;
            }

            if (isset($item->ARTIST[0]->VALUE)) {
                $formatted->album_artist = $item->ARTIST[0]->VALUE;
            }

            if (isset($item->GENRE[0]->VALUE)) {
                $formatted->album_genre = $item->GENRE[0]->VALUE;
            }

            if (isset($item->DATE[0]->VALUE)) {
                $formatted->album_year = $item->DATE[0]->VALUE;
            }

            if (isset($item->TRACK_COUNT)) {
                $formatted->track_count = $item->TRACK_COUNT;
            }

            if (isset($item->TRACK)) {
                $formatted->tracks = collect($item->TRACK)->map(function ($item, $key) {
                    $formatted = (object) [];
                    if (isset($item->TRACK_NUM)) {
                        $formatted->track_number = $item->TRACK_NUM;
                    }

                    if (isset($item->GN_ID)) {
                        $formatted->gracenote_track_id = $item->GN_ID;
                    }

                    if (isset($item->ARTIST[0]->VALUE)) {
                        $formatted->artist = $item->ARTIST[0]->VALUE;
                    }

                    if (isset($item->TITLE[0]->VALUE)) {
                        $formatted->title = $item->TITLE[0]->VALUE;
                    }

                    return $formatted;
                })->toArray();
            }

            return $formatted;
        });
    }

    private function formatSearchTrackTitle($raw_albums)
    {
        return collect($raw_albums)->map(function ($item, $key) {
            $formatted = (object) [];

            if (isset($item->GN_ID)) {
                $formatted->gracenote_album_id = $item->GN_ID;
            }

            if (isset($item->TITLE[0]->VALUE)) {
                $formatted->album_title = $item->TITLE[0]->VALUE;
            }

            if (isset($item->ARTIST[0]->VALUE)) {
                $formatted->album_artist = $item->ARTIST[0]->VALUE;
            }

            if (isset($item->GENRE[0]->VALUE)) {
                $formatted->album_genre = $item->GENRE[0]->VALUE;
            }

            if (isset($item->DATE[0]->VALUE)) {
                $formatted->album_year = $item->DATE[0]->VALUE;
            }

            if (isset($item->TRACK[0]->TRACK_NUM)) {
                $formatted->track_number = $item->TRACK[0]->TRACK_NUM;
            }

            if (isset($item->TRACK[0]->GN_ID)) {
                $formatted->gracenote_track_id = $item->TRACK[0]->GN_ID;
            }

            if (isset($item->TRACK[0]->TITLE)) {
                $formatted->track_title = $item->TRACK[0]->TITLE[0]->VALUE;
            }

            return $formatted;
        });
    }

    /**
     * Sets the XML payload
     */
    private function xmlPayload()
    {
        $lang = "<LANG>{strtoupper($this->lang)}</LANG>";
        $auth= "<AUTH><CLIENT>{$this->client_id}-{$this->client_tag}</CLIENT><USER>{$this->user_id}</USER></AUTH>";
        $search = '<TEXT TYPE="'.strtoupper($this->search_type).'">'.$this->search_terms.'</TEXT>';
        $query = '<QUERY CMD="'.$this->query_cmd.'">'.$search.'</QUERY>';
        $payload = "<QUERIES>{$lang}{$auth}{$query}</QUERIES>";
        
        return $payload;
    }

    /**
     * This will try to set required information to be found in config/env file,
     * and throw an exception if nothing is found.
     */
    private function setParameters()
    {
        if (empty(config('laravel-gracenote.client_id'))) {
            throw RequiredConfigMissing::cantFindClientId();
        }

        if (empty(config('laravel-gracenote.client_tag'))) {
            throw RequiredConfigMissing::cantFindClientTag();
        }

        if (empty(config('laravel-gracenote.user_id'))) {
            throw RequiredConfigMissing::cantFindUserId();
        }

        $this->cache = empty(config('laravel-gracenote.cache')) ? 60 : config('laravel-gracenote.cache');
        $this->client_id = config('laravel-gracenote.client_id');
        $this->client_tag = config('laravel-gracenote.client_tag');
        $this->user_id = config('laravel-gracenote.user_id');
        $this->request_url = "https://c{$this->client_id}.web.cddbp.net/webapi/json/1.0/";
    }
}
