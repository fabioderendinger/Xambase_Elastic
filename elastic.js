// *****************************************
// MAPPING
// *****************************************

{
   "xambase_index": {
      	"mappings": {
         	"document": {
            	"properties": {
	            	"id": {
	                  "type": "long"
	            	},
					"user_id": {
	                  "type": "integer"
	            	},
					"document_type": {
	                  "type": "byte",
	            	},
					"document_title": {
	                  "type": "text",
	                  "analyzer": "standardAnalyzer",
	                  "fields": {
	                  	// Multi-field mapping: See https://www.elastic.co/guide/en/elasticsearch/guide/current/most-fields.html
                        "ngram":   { 
                            "type":     "text",
                            "analyzer": "ngramAnalyzer"
                        }
                      }
	            	},
					"document_sub_title": {
	                  "type": "text",
	                  "analyzer": "standardAnalyzer",
	                  "fields": {
	                  	// Multi-field mapping: See https://www.elastic.co/guide/en/elasticsearch/guide/current/most-fields.html
                        "ngram":   { 
                            "type":     "text",
                            "analyzer": "ngramAnalyzer"
                        }
                      }
	            	},
					//"images": {
	                //  "type": "..."
	            	//},
					"school_id": {
	                  "type": "integer"
	            	},
					"subject_id": {
	                  "type": "integer"
	            	},
					"teacher_id": {
	                  "type": "integer"
	            	},
					"term_id": {
	                  "type": "integer"
	            	},
					"course_id": {
	                  "type": "integer"
	            	},
					"year": {
	                  "type": "integer"
	            	},
					"month":{
	                  "type": "byte"
	            	},
					"grade":{
	                  "type": "float"
	            	},
					"weight":{
	                  "type": "float"
	            	},
					"published_as": {
	                  "type": "keyword"
	            	},
					"is_draft": {
	                  "type": "byte"
	            	},
					"status": {
	                  "type": "byte"
	            	},
					"updated_at": {
	                  "type": "date"
	            	},
					"created_at": {
	                  "type": "date"
	            	},
					"school":	{
					  	"type": "nested",
					  	"properties": {
						  "id": {
	                  	    "type": "integer"
	            		  },
						  "school_name": {
	                  	    "type": "keyword"
	            		  },
						  "abbr": {
	                  	    "type": "keyword"
	            		  },
						  "language_id": {
	                  	    "type": "short"
	            		  },
						  "country_id": {
	                  	    "type": "short"
	            		  },
						  "is_university": {
	                  	    "type": "byte"
	            		  },
						  "status": {
	                  	    "type": "byte"
	            		  },
						  "updated_at": {
	                  	    "type": "date"
	            		  },
						  "created_at": {
	                  	    "type": "date"
	            		  },
	            		  // What's the reason we need the complete country information in the document?...
	            		  /*
						"country":	{
							"id":1,
							"country_name":"India",
							"status":1,
							"updated_at":"2017-01-22 20:41:22",
							"created_at":"2017-01-23 02:02:49"
							}
						  */
						}
					},

					"subject":	{
						"type": "nested",
				  		"properties": {
					  	  "id": {
                  	    	"type": "integer"
                  	      },
						  "subject_name": {
                  	    	"type": "keyword",
		                    "fields": {
		                  	  // Multi-field mapping: See https://www.elastic.co/guide/en/elasticsearch/guide/current/most-fields.html
	                          "ngram": { 
	                              "type":     "text",
	                              "analyzer": "ngramAnalyzer"
                        	  }
                      		}
                      	  },
						  "school_id": {
                  	    	"type": "integer"
                  	      },
						  "status": {
                  	    	"type": "byte"
                  	      },
						  "updated_at": {
                  	    	"type": "date"
                  	      },
						  "created_at": {
                  	    	"type": "date"
                  	      },							
                  	  	}
                  	},

					"teacher":	{
						"type": "nested",
				  		"properties": {
					  	  "id": {
                  	    	"type": "integer"
                  	      },
						  "school_id": {
                  	    	"type": "integer"
                  	      },
						  "first_name": {
                  	    	"type": "keyword"
                  	      },
						  "last_name": {
                  	    	"type": "keyword"
                  	      },
						  "status": {
                  	    	"type": "byte"
                  	      },
						  "updated_at": {
                  	    	"type": "date"
                  	      },
						  "created_at": {
                  	    	"type": "date"
                  	      },
						}
					}

					"course":	{
						"type": "nested",
				  		"properties": {
					  	  "id": {
                  	    	"type": "integer"
                  	      },
						  "subject_id": {
                  	    	"type": "integer"
                  	      },
						  "course_name": {
                  	    	"type": "keyword"
                  	      },
						  "status": {
                  	    	"type": "byte"
                  	      },
						  "updated_at": {
                  	    	"type": "date"
                  	      },
						  "created_at": {
                  	    	"type": "date"
                  	      },
						}
					}
				}
			}
		}
	}
}



// *****************************************
// ANALYZERS
// *****************************************

{
  	"settings" : {
        "analysis" : {
    		    "filter" : {
                "quadgrams_filter" : {  
                    "type":     "ngram",
                    "min_gram": 4,
                    "max_gram": 4
                },
                "autocomplete_filter": {
                  	"type":     "edge_ngram",
                  	"min_gram": 1,
                  	"max_gram": 20
  		          }
    		    },
        		"analyzer": {
              	"standardAnalyzer": {
      	          	"type": "custom",
      	          	"tokenizer": "standard"
      	          	"filter": [
      	          		"asciifolding",
      	            	"lowercase",
      	          	]
              	},
              	"ngramAnalyzer": {
      	          	"type": "custom",
      	          	"tokenizer": "standard"
      	          	"filter": [
      	          		"asciifolding",
      	            	"lowercase",
      	            	"quadgrams_filter"
      	          	]
              	},
              	"_allAnalyzer": {
      	          	"type": "custom",
      	          	"tokenizer": "standard"
      	          	"filter": [
      	          		"asciifolding",
      	            	"lowercase",
      	            	"autocomplete_filter"
      	          	]
                }      	        		        	
            }
        }	
  	}
}















// *****************************************
// QUERY FOR SEARCH SCREEN
// *****************************************

// Scenario 1 (only TitleField filled)
// TitleField: $TitleField
// SchoolField: empty
// SubjectField: empty
// TeacherField: empty

{
    "query" : {
        "bool" : { 
            "filter" : [
                { "term" : { "document_type" : $document_type}},
                { "term" : { "is_draft" : 0}},
                { "term" : { "status" : 1}},
                { "range": { "year": { "gte": $starYear, 'lte' => $endYear }}},
                { "nested": {
                	// Querying Nested Objects: https://www.elastic.co/guide/en/elasticsearch/guide/current/nested-query.html
                	"path": "school",
                	"query": {
                		"bool": {
                			"filter": {
                				{ "term" : { "country_id" : $country_id}},
                				{ "term" : { "language_id" : $language_id}},
               					{ "term" : { "is_university" : $is_university}},
                			}
                		}
                	}
                  }
            	}
            ],
            "must" : {
            	"multi_match": {
            		// Multi-match: See https://www.elastic.co/guide/en/elasticsearch/guide/current/most-fields.html
		            "query": $TitleField,
		            "type": "most_fields",
		            "fields": [ "document_title^4", "document_title.ngram^2", "document_sub_title", "document_sub_title.ngram",  ]
            	}	
        	}
    	}
	}
	"sort": [
		{ "_score" : { "order": "desc" }},
        { "year" :   { "order": "desc" }}
    ]
}


// Scenario 2
// TitleField: $TitleField
// SchoolField: $school_id
// SubjectField: $subject_id or empty
// TeacherField: $teacher_id or empty

{
    "query" : {
        "bool" : { 
            "filter" : [
                { "term" : { "document_type" : $document_type}},
                { "term" : { "is_draft" : 0}},
                { "term" : { "status" : 1}},
                { "range": { "year" : { "gte" : $starYear, 'lte' : $endYear }}},
                { "nested": {
                	// Querying Nested Objects: https://www.elastic.co/guide/en/elasticsearch/guide/current/nested-query.html
                	"path": "school",
                	"query": {
                		"bool": {
                			"filter": {
                				{ "term" : { "country_id" : $country_id}},
                				{ "term" : { "language_id" : $language_id}},
               					{ "term" : { "is_university" : $is_university}},
                			}
                		}
                	}
                  }
            	}
            ],
            "must" : {
            	"multi_match": {
            		// Multi-match: See https://www.elastic.co/guide/en/elasticsearch/guide/current/most-fields.html
		            "query": $TitleField,
		            "type": "most_fields",
		            "fields": [ "document_title^4", "document_title.ngram^2", "document_sub_title", "document_sub_title.ngram",  ]
            	}	
        	},
        	"should" : {
        		"bool" : {
        			"must" : [
        				{ "term" : { "school_id" : $school_id}},
        				{ "term" : { "subject_id" : $subject_id}},
        				{ "term" : { "teacher_id" : $teacher_id}}
        			]
        		}
        	}
    	}
	}
	"sort": [
		{ "_score" : { "order": "desc" }},
        { "year" :   { "order": "desc" }}
    ]
}




// Scenario 3
// TitleField: $TitleField
// SchoolField: empty
// SubjectField: $SubjectField
// TeacherField: empty

{
    "query" : {
        "bool" : { 
            "filter" : [
                { "term" : { "document_type" : $document_type}},
                { "term" : { "is_draft" : 0}},
                { "term" : { "status" : 1}},
                { "range": { "year": { "gte": $starYear, 'lte' => $endYear }}},
                { "nested": {
                	// Querying Nested Objects: https://www.elastic.co/guide/en/elasticsearch/guide/current/nested-query.html
                	"path": "school",
                	"query": {
                		"bool": {
                			"filter": {
                				{ "term" : { "country_id" : $country_id}},
                				{ "term" : { "language_id" : $language_id}},
               					{ "term" : { "is_university" : $is_university}},
                			}
                		}
                	}
                  }
            	}
            ],
            "must" : {
            	"multi_match": {
            		// Multi-match: See https://www.elastic.co/guide/en/elasticsearch/guide/current/most-fields.html
		            "query": $TitleField,
		            "type": "most_fields",
		            "fields": [ "document_title^4", "document_title.ngram^2", "document_sub_title", "document_sub_title.ngram",  ]
            	}	
        	},
        	"should" : {
        		{ "nested": {
                	// Querying Nested Objects: https://www.elastic.co/guide/en/elasticsearch/guide/current/nested-query.html
                	"path": "subject",
                	"query": {
                		"multi-match" : { 
	            		// Multi-match: See https://www.elastic.co/guide/en/elasticsearch/guide/current/most-fields.html
			            "query": $SubjectField,
			            "type": "most_fields",
			            "fields": [ "subject_name^2", "subject_name.ngram" ]
                		}
                	}
                  }
            	}
        	}
    	}
	}
	"sort": [
		{ "_score" : { "order": "desc" }},
        { "year" :   { "order": "desc" }}
    ]
}




// Scenario 4
// TitleField: empty
// SchoolField: empty
// SubjectField: $SubjectField
// TeacherField: empty

{
    "query" : {
        "bool" : { 
            "filter" : [
                { "term" : { "document_type" : $document_type}},
                { "term" : { "is_draft" : 0}},
                { "term" : { "status" : 1}},
                { "range": { "year": { "gte": $starYear, 'lte' => $endYear }}},
                { "nested": {
                	// Querying Nested Objects: https://www.elastic.co/guide/en/elasticsearch/guide/current/nested-query.html
                	"path": "school",
                	"query": {
                		"bool": {
                			"filter": {
                				{ "term" : { "country_id" : $country_id}},
                				{ "term" : { "language_id" : $language_id}},
               					{ "term" : { "is_university" : $is_university}},
                			}
                		}
                	}
                  }
            	}
            ],
        	"must" : {
        		{ "nested": {
                	// Querying Nested Objects: https://www.elastic.co/guide/en/elasticsearch/guide/current/nested-query.html
                	"path": "subject",
                	"query": {
                		"multi-match" : { 
	            		// Multi-match: See https://www.elastic.co/guide/en/elasticsearch/guide/current/most-fields.html
			            "query": $SubjectField,
			            "type": "most_fields",
			            "fields": [ "subject_name^2", "subject_name.ngram" ]
                		}
                	}
                  }
            	}
        	}
    	}
	}
	"sort": [
		{ "_score" : { "order": "desc" }},
        { "year" :   { "order": "desc" }}
    ]
}




// Scenario 5
// TitleField: empty
// SchoolField: $school_id
// SubjectField: empty
// TeacherField: empty

{
    "query" : {
        "bool" : { 
            "filter" : [
                { "term" : { "document_type" : $document_type}},
                { "term" : { "is_draft" : 0}},
                { "term" : { "status" : 1}},
                { "range": { "year" : { "gte" : $starYear, 'lte' : $endYear }}},
                { "nested": {
                	// Querying Nested Objects: https://www.elastic.co/guide/en/elasticsearch/guide/current/nested-query.html
                	"path": "school",
                	"query": {
                		"bool": {
                			"filter": {
                				{ "term" : { "country_id" : $country_id}},
                				{ "term" : { "language_id" : $language_id}},
               					{ "term" : { "is_university" : $is_university}},
                			}
                		}
                	}
                  }
            	},
            	{ "term" : { "school_id" : $school_id}}
            ],
    	}
	}
	"sort" : { "id" :   { "order": "desc" }}
}





// *****************************************
// QUERY FOR MY UPLOADS SCREEN
// *****************************************

{
    "query" : {
        "bool" : { 
            "filter" : { "term" : { "status" : 1}},
            "must" : {
            	"multi_match": {
            		// Multi-match: See https://www.elastic.co/guide/en/elasticsearch/guide/current/most-fields.html
		            "query": $SearchValue,
		            "type": "most_fields",
		            "fields": [ "document_title^4", "document_title.ngram^2", "document_sub_title", "document_sub_title.ngram",  ]
            	}	
        	},
        	"should" : {
        		{ "nested": {
                	// Querying Nested Objects: https://www.elastic.co/guide/en/elasticsearch/guide/current/nested-query.html
                	"path": "subject",
                	"query": {
                		"multi-match" : { 
	            		// Multi-match: See https://www.elastic.co/guide/en/elasticsearch/guide/current/most-fields.html
			            "query": $SubjectField,
			            "type": "most_fields",
			            "fields": [ "subject_name^2", "subject_name.ngram" ]
                		}
                	}
                  }
            	}
        	}
    	}
	}
	"sort": [
		{ "_score" : { "order": "desc" }},
        { "year" :   { "order": "desc" }}
    ]
}

















// *****************************************
// *****************************************
// NOT USED (SAMPLE DOCUMENT)
// *****************************************
// *****************************************


{
	"id":1,
	"user_id":6,
	"document_type":1,
	"document_title":"Course",
	"document_sub_title":"university ",
	"images":["http://35.160.4.120/storage/documents/m5YBczH1N294IBv20170721192050.png"],
	"school_id":7,
	"subject_id":9,
	"teacher_id":19,
	"term_id":null,
	"course_id":1,
	"year":2017,
	"month":"07",
	"grade":"",
	"weight":"",
	"published_as":"anonymously",
	"is_draft":0,
	"status":1,
	"updated_at":"2017-07-21 19:20:50",
	"created_at":"2017-07-21 19:20:50",
	"school":	{
		"id":7,
		"school_name":"UNIVERSITY A",
		"abbr":"UNIA",
		"language_id":1,
		"country_id":1,
		"is_university":1,
		"status":1,
		"updated_at":"2017-07-18 15:47:35",
		"created_at":"2017-07-18 15:47:35",
		"country":	{
			"id":1,
			"country_name":"India",
			"status":1,
			"updated_at":"2017-01-22 20:41:22",
			"created_at":"2017-01-23 02:02:49"
			}
		},

		"subject":	{
			"id":9,
			"subject_name":"Math",
			"school_id":7,
			"status":1,
			"updated_at":"2017-07-18 15:47:48",
			"created_at":"2017-07-18 15:47:48"
			},

		"teacher":	{
			"id":19,
			"school_id":7,
			"first_name":"Kaml",
			"last_name":"Ku",
			"status":1,
			"updated_at":"2017-07-21 08:47:28",
			"created_at":"2017-07-21 08:47:28"
			},

		"course":	{
			"id":1,
			"subject_id":9,
			"course_name":"Course",
			"status":1,
			"updated_at":"2017-07-21 16:46:47",
			"created_at":"2017-07-21 16:46:47"
			}
}






// *****************************************
// *****************************************
// NOT USED
// *****************************************
// *****************************************
                { "term" : { "school.country_id" : $country_id}},
                { "term" : { "school.language_id" : $language_id}},
                { "term" : { "school.is_university" : $is_university}},