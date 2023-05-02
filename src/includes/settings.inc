{
	"appname" : "harubi-front",
	"language" : "en",
	"new_user_role" : "member",
	"page_size" : 25,
	"frepo_rel_root" : "../../filerepo",
	"logs" : 0,
	
	"session" : {
		"timeout" : 7200,
		"max_signins_per_week" : 10,
		"remember_me_days" : 30
	},
	
    "mysql" : {
        "hostname" : "localhost",
        "username" : "",
        "password" : "",
        "database" : "harubi-front",
        "prefix"   : ""
    },

    "tables" : {
        "user" : {
            "id" : "int",
            "avatar" : "string",
            "name" : "string",
            "password" : "string",
            "email" : "string",
            "roleid" : "int",
            "language" : "string",
            "valid_thru" : "int",
            "signins" : "int",
            "last_signedin_utc" : "int",
            "signin_note" : "int",
            "created_utc" : "int",
            "updated_utc" : "int"
        },
        "remember_me" : {
            "id" : "int",
            "userid" : "int",
            "userstr" : "string",
            "created_utc" : "int"
        },
        "usergroup" : {
            "id" : "int",
            "name" : "string",
            "ref" : "string",
            "owneruserid" : "int",
            "created_by" : "int",
            "created_utc" : "int",
            "updated_utc" : "int"
        },
        "member" : {
            "id" : "int",
            "userid" : "int",
            "usergroupid" : "int",
            "roleid" : "int",
            "unread" : "int",
            "added_by" : "int",
            "created_utc" : "int",
            "updated_utc" : "int"
        },
        "discussion_type" : {
            "id" : "int",
            "name" : "string",
            "autofollow" : "int"
        },
        "discussion" : {
            "id" : "int",
            "ref" : "string",
            "name" : "string",
            "discussion_type_id" : "int",
            "autofollow" : "int"
        },
        "discussion_follower" : {
            "id" : "int",
            "discussion_id" : "int",
            "userid" : "int",
            "created_utc" : "int"
        },
        "post" : {
            "id" : "int",
            "discussion_id" : "int",
            "body" : "string",
            "attachment" : "string",
            "quote_id" : "int",
            "quote_discussion_id" : "int",
            "posted_by" : "int",
            "created_utc" : "int",
            "updated_utc" : "int"
        },
        "postread" : {
            "id" : "int",
            "userid" : "int",
            "discussion_id" : "int",
            "discussion_type_id" : "int",
            "lastread_postid" : "int",
            "created_utc" : "int",
            "updated_utc" : "int"
        },
        "postreact" : {
            "id" : "int",
            "postid" : "int",
            "userid" : "int",
            "type" : "int"
        },
        "role" : {
            "id" : "int",
            "name" : "string",
            "premium" : "int"
        },
        "permission" : {
            "id" : "int",
            "name" : "string"
        },
        "permrole" : {
            "id" : "int",
            "permissionid" : "int",
            "roleid" : "int"
        },
        "preference" : {
            "id" : "int",
            "name" : "string",
            "value" : "string"
        }
    }
}
