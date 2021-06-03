<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>result</title>
    <style>
        /* body:after {

            content: '{{ $chat_id }}';

            background-color: #333333aa;

            position: fixed;

            top: 0;

            left: 0;

            color: white;

            padding: 10px;

            box-shadow: 0 2px 0 green;

            width: 100%;

            font-family: comic sans ms;
        } */

        .container {
            /*
            position: absolute;

            top: 50%;

            left: 50%;

            transform: translate(-50%, -50%);

            width: 100%; */

        }

        .table {

            display: flex;

            justify-content: center;

            align-items: center;

            flex-direction: column;

        }

        h1 {

            background-color: #eee;

            padding: 10px;

            position: relative;

        }

        h1:before {

            content: '';

            border-width: 10px;

            border-style: solid;

            border-color: #eee transparent transparent transparent;

            position: absolute;

            bottom: -20px;

            left: calc(50% - 10px);

        }

        table {

            width: 90vw;

            font-family: Sans-Serif;

            box-shadow: 0 5px 0 rgb(15, 157, 88);

        }

        table thead {

            background-color: #333;

            color: white;

            text-align: center;

            text-transform: uppercase;

        }

        table thead tr td {

            padding: 10px;

        }

        table thead .special {

            width: 30%;

        }

        table tbody {

            background-color: #eee;

            color: #111;

            text-align: center;

        }

        table tbody tr td {

            padding: 10px 20px;

        }

        table tbody img {

            width: 50px;

        }

        table tbody .control {

            display: inline-block;

        }

        .view,
        .delete {

            padding: 1px 5px;

            margin-bottom: 5px;

            color: white;

            font-weight: bold;

            background-color: rgb(219, 68, 55);

            user-select: none;

        }

        .view {

            background-color: rgb(66, 133, 244);

        }

    </style>
</head>

<body>
    <div class="container table">
        <br>

        chat id : {{ $chat_id }}
        <br>
        <br>
        <br>
        <br>
        <br>
        {{-- name : {{$name}} --}}

        <table style="margin-top: 80px;">

            <!--header-->

            <thead>

                <tr>

                    <td>amount</td>

                    <td>status</td>

                    <td>Request time</td>

                    <td>last Update</td>

                </tr>

            </thead>

            <!--body-->

            <tbody>

                <!--one-->
                @foreach ($dep as $d)
                    <tr>



                        <td >{{ $d['amount'] }}</td>

                        <td>
                            @if ($d['payed'])
                                Paid
                            @else
                                not Paid
                            @endif
                        </td>

                        <td>{{ $d['created_at'] }}</td>


                        <td>{{ $d['updated_at'] }}</td>

                    </tr>
                @endforeach



            </tbody>

        </table>

    </div>
</body>

</html>
