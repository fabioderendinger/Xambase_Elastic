<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Elasticquent\ElasticquentTrait;

class Document extends Model
{
	use ElasticquentTrait;

	protected $mappingProperties = [
        "id"=> [
            "type"=> "long"
          ],
              "user_id"=> [
            "type"=> "integer"
          ],
          "document_type"=> [
            "type"=> "byte",
          ],
              "document_title"=> [
            "type"=> "text",
            "index_options"=> "freqs",
            "include_in_all"=> true,
            "analyzer"=> "standardAnalyzer",
            "fields"=> [
              "ngram"=>[ 
                "type"=>"text",
                "index_options"=>"freqs",
                "analyzer"=> "ngramAnalyzer"
              ]
            ]
          ],
              "document_sub_title"=> [
            "type"=> "text",
            "index_options"=> "freqs",
            "include_in_all"=> true,
            "analyzer"=> "standardAnalyzer",
            "fields"=> [
            "ngram"=>   [ 
                "type"=>     "text",
                "index_options"=> "freqs",
                "analyzer"=> "ngramAnalyzer"
              ]
            ]
            ],
            "images"=> [
               "type"=> "text"
            ],
                "school_id"=> [
            "type"=> "integer"
          ],
                "subject_id"=> [
            "type"=> "integer"
          ],
                "teacher_id"=> [
            "type"=> "integer"
          ],
                "term_id"=> [
            "type"=> "integer"
          ],
                "course_id"=> [
            "type"=> "integer"
          ],
            "year"=> [
            "type"=> "integer"
          ],
                "month"=>[
            "type"=> "byte",
            "index"=> "no"
          ],
                "grade"=>[
            "type"=> "float",
            "index"=> "no"
          ],
                "weight"=>[
            "type"=> "float"
          ],
                "published_as"=> [
            "type"=> "keyword"
          ],
                "is_draft"=> [
            "type"=> "byte"
          ],
                "status"=> [
            "type"=> "byte"
          ],
                "updated_at"=> [
            "type"=> "date",
            "format"=>"yyyy-MM-dd HH:mm:ss"
          ],
                "created_at"=> [
            "type"=> "date",
            "format"=>"yyyy-MM-dd HH:mm:ss"
          ],
            "school"=>   [
                  "type"=> "nested",
                  "properties"=> [
                    "id"=> [
                "type"=> "integer"
              ],
                      "school_name"=> [
                "type"=> "keyword",
                "include_in_all"=> true
                  ],
                      "abbr"=> [
                "type"=> "keyword",
                "include_in_all"=> true
              ],
                      "language_id"=> [
                "type"=> "short"
              ],
                      "country_id"=> [
                "type"=> "short"
              ],
                      "is_university"=> [
                "type"=> "byte"
              ],
                      "status"=> [
                "type"=> "byte"
              ],
                      "updated_at"=> [
                "type"=> "date",
                "format"=>"yyyy-MM-dd HH:mm:ss"
              ],
                      "created_at"=> [
                "type"=> "date",
                "format"=>"yyyy-MM-dd HH:mm:ss"
              ],
              "country"=>  [
                  "type"=> "nested",
                  "properties"=> [
                    "id"=> [
                "type"=> "integer"
              ],
                      "country_name"=> [
                "type"=> "keyword",
                "include_in_all"=> true,
                "fields"=> [
                "ngram"=> [ 
                    "type"=>     "text",
                    "index_options"=> "freqs",
                    "analyzer"=> "ngramAnalyzer"
                  ]
                ]
                  ]
              ]
                    ]
              ]
              ],

                "subject"=>  [
                    "type"=> "nested",
                "properties"=> [
                  "id"=> [
                "type"=> "integer"
              ],
                    "subject_name"=> [
                "type"=> "keyword",
                "include_in_all"=> true,
                "fields"=> [
                "ngram"=> [ 
                    "type"=>     "text",
                    "index_options"=> "freqs",
                    "analyzer"=> "ngramAnalyzer"
                  ]
                ]
              ],
                      "school_id"=> [
                "type"=> "integer"
              ],
                      "status"=> [
                "type"=> "byte"
              ],
                      "updated_at"=> [
                "type"=> "date",
                "format"=>"yyyy-MM-dd HH:mm:ss"
              ],
                      "created_at"=> [
                "type"=> "date",
                "format"=>"yyyy-MM-dd HH:mm:ss"
              ],                            
            ]
          ],

                "teacher"=>  [
                    "type"=> "nested",
                "properties"=> [
                  "id"=> [
                "type"=> "integer"
              ],
                    "school_id"=> [
                "type"=> "integer"
              ],
                    "first_name"=> [
                "type"=> "keyword",
                "include_in_all"=> true
              ],
                    "last_name"=> [
                "type"=> "keyword",
                "include_in_all"=> true
              ],
                    "status"=> [
                "type"=> "byte"
              ],
                    "updated_at"=> [
                "type"=> "date",
                "format"=>"yyyy-MM-dd HH:mm:ss"
              ],
                    "created_at"=> [
                "type"=> "date",
                "format"=>"yyyy-MM-dd HH:mm:ss"
              ]
            ]
        ],

                "course"=>   [
                "type"=> "nested",
              "properties"=> [
                    "id"=> [
                    "type"=> "integer"
              ],
                      "subject_id"=> [
                "type"=> "integer"
              ],
                      "course_name"=> [
                "type"=> "keyword"
              ],
                      "status"=> [
                "type"=> "byte"
              ],
                      "updated_at"=> [
                "type"=> "date",
                "format"=>"yyyy-MM-dd HH:mm:ss"
              ],
                      "created_at"=> [
                "type"=> "date",
                "format"=>"yyyy-MM-dd HH:mm:ss"
              ]
            ]  
        ]
    ];

  function getTypeName(){
    return "document";
  }

  public function user()
	{
		return $this->belongsTo('App\User');
	}
	
	public function school()
	{
		return $this->belongsTo('App\School');
	}
	
	public function subject()
	{
		return $this->belongsTo('App\Subject');
	}
	
	public function teacher()
	{
		return $this->belongsTo('App\Teacher');
	}
	
	public function term()
	{
		return $this->belongsTo('App\Term');
	}

	public function course()
	{
		return $this->belongsTo('App\Course', 'course_id');
	}

    public function ratings()
    {
        return $this->hasMany('App\DocumentRating');
    }

    public function favourites()
    {
        return $this->hasMany('App\DocumentFavourite');
    }

    public function reviews()
    {
        return $this->hasMany('App\Review');
    }
}

