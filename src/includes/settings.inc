{
	"appname" : "agro-diseases",
	"language" : "ms",
	
	"session" : {
		"timeout" : 7200,
		"max_signins_per_week" : 10
	},
	
	"new_user_role" : "member",
	"logs" : 0,
	"page_size" : 25,
	
    "mysql" : {
        "hostname" : "localhost",
        "username" : "root",
        "password" : "asus",
        "database" : "agro-diseases",
        "prefix"   : ""
    },

    "tables" : {
        "user" : {
            "id" : "int",
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
        "post_parent" : {
            "id" : "int",
            "ref" : "string",
            "name" : "string"
        },
        "post" : {
            "id" : "int",
            "post_parent_id" : "int",
            "body" : "string",
            "attachment" : "string",
            "posted_by" : "int",
            "created_utc" : "int",
            "updated_utc" : "int"
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
