
<!doctype html>
<html lang="en">
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>Bazi</title>
        <script src="{{asset('asset/js/jquery-3.3.1.min.js')}}"></script>
        <link rel="stylesheet" href="{{asset('asset/css/bootstrap3.3.7.min.css')}}" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
        <script src="{{asset('asset/js/gijgo.min.js')}}" type="text/javascript"></script>
        <link href="https://unpkg.com/gijgo@1.9.13/css/gijgo.min.css" rel="stylesheet" type="text/css" />
        <style>
            body{
                background-color: whitesmoke;
                padding:50px;
            }

            .inner{
                width: 70%;
                padding: 2em;
                height: 50%;
                top: 10%;
                margin: 0 auto;
                position: relative;
                background:orange;
            }

            .row{
                background-color: white;
                border: 1px solid #ccc;
                height: 1000px;padding: 2em;
            }

        </style>
    </head>
    <body>  
        <div class="container">
            
              @yield('content')
           
        </div>
        <script>
            $('#datepicker').datepicker();
        </script>
        <script src="{{asset('asset/js/bootstrap4.0.0.min.js')}}" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    </body>
</html>