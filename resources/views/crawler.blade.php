<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>VCF to FHIR</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Raleway', sans-serif;
                font-weight: 100;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 67vh;
            }
            pre {
                color: red;
                text-align: center;
            }
            .error-message {
                color: red;
                font-family: initial;
            }
            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .top-right {
                position: absolute;
                right: 10px;
                top: 18px;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 84px;
            }
            .links {
                margin-top: 4%;
            }
            .links > a {
                color: #005dff;
                padding: 0 25px;
                font-size: 12px;
                font-weight: 600;
                letter-spacing: .1rem;
                text-decoration: underline;
                text-transform: uppercase;
            }

            .m-b-md {
                margin-bottom: 30px;
            }
            .select-file {
                font-family: -webkit-body;
            }
            .pre {
                text-align: center;
                margin-top: 37px;
            }
 
        </style>
    </head>
    <body>
        <div class="flex-center position-ref full-height">
           

            <div class="content">
                <div class="title m-b-md">
                   VCF-TO-FHIR </br>
                   CONVERTER
                </div>

              
                <form action="translate" method="post" enctype="multipart/form-data" style="margin-top: 5%;">

                   <span class="select-file">
                    Select file to upload:
                   </span>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="file" name="fileToUpload" id="fileToUpload">
                    <input type="submit" value="Click To Convert" name="submit">
                </form>

                @if ($status)
                <div class="links">
                    <span class="select-file"> fhir.xml and fhir.json has been successfully created </span>
                </br>  </br>
                    <a class="view-xml" target="_blank" href="converted/fhir.xml">View XML file</a>
                    <a class="view-xml" target="_blank" href="converted/fhir.json">View JSON file</a>
                </div>

                @endif
                <div class="links">
                     <a class="view-xml" target="_blank" href="https://github.com/openelimu/VCF-2-FHIR/blob/master/README.md">Readme</a>
                </div>

                @if ($message)
                <div class="links">
                    <span class="select-file"> {{ $message }} </span>
                
                @endif

               
               @if ( isset($isInvalidValidBuild) ? $isInvalidValidBuild : '')
                <div class="links">
                    <span class="error-message"> Invalid Build</span>
                <div>
                @endif

                  @if ( isset($isInvalidValidGenetype) ? $isInvalidValidGenetype : '')
                <div class="links">
                    <span class="error-message"> {{$isInvalidValidGenetype}} </span>
                </div>
                @endif
              
                

                
            </div>
        </div>
    </body>
</html>
