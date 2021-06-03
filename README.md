## 租户生成的conf文件模板
        server{
            listen {{Arr::get($config, 'ports.http', 80) }};
            server_name {{ $hostname->vue }};
            index index.php index.html index.htm default.php default.htm default.html;
            root /www/wwwroot/tenancy;
            @if($hostname->ishttp===1)
                listen 443 ssl http2;
                ssl_protocols TLSv1.1 TLSv1.2;
                ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:HIGH:!aNULL:!MD5:!RC4:!DHE;
                ssl_prefer_server_ciphers on;
                ssl_session_cache shared:SSL:10m;
                ssl_session_timeout 10m;
                @if($hostname->type==='secondary')
                    ssl_certificate {{public_path()}}/server.crt;
                    ssl_certificate_key {{public_path()}}/server.key;
                @endif
                @if($hostname->type==='tenant')
                    ssl_certificate {{storage_path('app/tenancy/tenants/')}}{{$website->uuid}}/server.crt;
                    ssl_certificate_key {{storage_path('app/tenancy/tenants/')}}{{$website->uuid}}/server.key;
                @endif
            @endif
            include enable-php-74.conf;
            location ~ ^/(\.user.ini|\.htaccess|\.git|\.svn|\.project|LICENSE|README.md)
            {
                return 404;
            }
            location ~ \.well-known
            {
                allow all;
            }
            location /api
            {
                proxy_redirect off;
                proxy_set_header Host {{$hostname->fqdn}};
                proxy_set_header REMOTE-HOST $remote_addr;
                proxy_set_header X-Real-IP $remote_addr;
                proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
                proxy_pass http://{{$hostname->fqdn}};
            }
            location /storage
            {
                proxy_redirect off;
                proxy_set_header Host {{$hostname->fqdn}};
                proxy_set_header REMOTE-HOST $remote_addr;
                proxy_set_header X-Real-IP $remote_addr;
                proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
                proxy_pass http://{{$hostname->fqdn}};
            }
            location /images
            {
                proxy_redirect off;
                proxy_set_header Host {{$hostname->fqdn}};
                proxy_set_header REMOTE-HOST $remote_addr;
                proxy_set_header X-Real-IP $remote_addr;
                proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
                proxy_pass http://{{$hostname->fqdn}};
            }
            location /wss
            {
                proxy_pass http://127.0.0.1:7272;
                proxy_http_version 1.1;
                proxy_set_header Upgrade $http_upgrade;
                proxy_set_header Connection "Upgrade";
                proxy_set_header X-Real-IP $remote_addr;
            }
        }
        server{
            listen 80;
            server_name {{$hostname->fqdn}};
            root {{public_path()}};
            index index.php;
            include enable-php-74.conf;
            location /
            {
                try_files $uri $uri/ /index.php$is_args$query_string;
            }
            location ~ ^/(\.user.ini|\.htaccess|\.git|\.svn|\.project|LICENSE|README.md)
            {
                return 404;
            }
            location ~ \.well-known
            {
                allow all;
            }
            location ~ .*\.(gif|jpg|jpeg|png|bmp|swf)$
            {
                expires      30d;
                error_log off;
                access_log /dev/null;
            }
            location ~ .*\.(js|css)?$
            {
                expires      12h;
                error_log off;
                access_log /dev/null;
            }
        }