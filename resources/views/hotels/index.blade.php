<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Top 3 Hotels for an Unforgettable Experience</title>
    <meta name="description" content="Discover the best deals on the top three hotels in Ireland. Explore our complete guide to find the lowest prices and book your perfect accommodation for an unforgettable stay.">
    <meta name="keywords" content="Ireland hotels, top hotels in Ireland, hotel price comparison, best hotel deals Ireland, affordable hotels Ireland, lowest hotel prices, hotel booking Ireland, accommodation in Ireland, luxury hotels Ireland, hotel discounts, price graph hotels Ireland, hotel price trends, Ireland travel deals, hotel reviews Ireland, family-friendly hotels Ireland" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Google chart Script -->
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <!-- End Google chart Script -->
    <!-- Latest compiled and minified JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script type="text/javascript">
      $(document).ready(function() {

            const data = @json( $data ??null );

            let datePrices = data;
            let currentCurrency = @json( $defaultCurrency ??null );

            $('#currency').change(function() 
            {
                loadData();
                currentCurrency = $( this ).val(); 
            });

            $('input[name="days"]').click(function() 
            {
                loadData(); 
            });

            $('input[name="pr1-pr2"]').click(function() 
            {
                loadData(); 
            });
            
            const drawChart = () => {

              const data = google.visualization.arrayToDataTable( datePrices );

              const options = {
                title: `Lowest Price Available for Each Hotel in ${currentCurrency}`,
                hAxis: { title: 'Date', format: 'yyyy-MM-dd', slantedText: true, slantedTextAngle: 45 },
                vAxis: { title: `Price (${currentCurrency})` },
                curveType: 'function',
                legend: { position: 'top' },
                colors: [ '#4169E1','#FFD700','#708090' ],
                backgroundColor: '#f0f0f0',
              }; 
               
              let chartContainer = $('#google_chart'); 
 
              let domElement = chartContainer[ 0 ];  
              const chart = new google.visualization.LineChart( domElement );

              chart.draw( data, options );
            }

            google.charts.load('current', {'packages':['corechart']});
            google.charts.setOnLoadCallback(drawChart);

            const onLoadData_Error = ( error ) => {

              showLoadingSpinner();  
              
              alert( 'We are sorry, but an error occurred while loading your data. Please try refreshing the page or try again later.' ); 

            }

            const onLoadData_Success = ( data ) => {

              datePrices = data?.data??[]; 

              drawChart(); 

              hideLoadingSpinner();
            }

            const fetchData = ( url, method, body, successCallback , errorCallback   ) => {

              const options = {
                method :method,
                headers: {
                  'Content-Type': 'application/json', 
                  'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                },
                body: body ? JSON.stringify(body) : null,
              };

              fetch( url, options )
                  .then(response => {

                      if (!response.ok) 
                      {
                          throw new Error('We encountered a problem with the network response. Please try again later.' + response.statusText);
                      }
                      return response.json();  

                  })
                  .then(data => {

                    successCallback( data );  

                  })
                  .catch(error => {

                    errorCallback(error ); 

                  });
            }

            const loadData = () => {

              showLoadingSpinner();
              const body = loadOptions();
              
              fetchData(
                '{{ route('hotels.inventory.horizon') }}',
                'POST', // HTTP method
                body, // Body content as an object
                onLoadData_Success,
                onLoadData_Error
              );

            }

            const loadOptions = () => {

              const days = $('input[name="days"]:checked').val();
              const toCurrency = $('#currency').val();
              const pr1OrPr2 = $('input[name="pr1-pr2"]:checked').val();

              const body = {
                days: days,
                toCurrency: toCurrency,
                pr1OrPr2 : pr1OrPr2
              }; 

              return body;
            }

            const showLoadingSpinner = ()=>{

              $('#loadingSpinner').show();

            }

            const hideLoadingSpinner = ()=>{

              $('#loadingSpinner').hide();

            }
        });
        
    </script>
  </head>

  <body>
  <header>
      <nav class="navbar navbar-expand-lg navbar-light bg-light">
          <div class="container-fluid">
              <a class="navbar-brand" href="#">Best hotel</a>
              <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                  <span class="navbar-toggler-icon"></span>
              </button>
              <div class="collapse navbar-collapse" id="navbarNav">
                  <ul class="navbar-nav">
                      <li class="nav-item">
                          <a class="nav-link active" aria-current="page" href="/">Home</a>
                      </li>
                      <li class="nav-item">
                          <a class="nav-link" href="/about">About</a>
                      </li>
                      <li class="nav-item">
                          <a class="nav-link" href="/services">Services</a>
                      </li>
                      <li class="nav-item">
                          <a class="nav-link" href="/contact">Contact</a>
                      </li>
                  </ul>
              </div>
          </div>
      </nav>
  </header>
  <main>
    <div class="container mt-5">

      @if(isset($data) && !empty($data))
      <section>
        <h1 class="text-center p-4">
          Stay in Style:
          <small class="text-body-secondary">The Top 3 Hotels for an Unforgettable Experience</small>
        </h1>
        <p class="lead"> Explore our selection of the top three hotels that redefine luxury and comfort. Each hotel offers a great location, exceptional service and unique amenities to ensure an unforgettable stay. Whether you are looking for romance, family fun or relaxation, these hotels promise an unforgettable experience tailored just for you.</P>
        <!-- Top 3 Hotels Section -->
        <div class="card-group">
          @foreach ( $propertyDetails["siteList"] as $site )
            <div class="card">
              <img src="https://{{$site['images'][0]['url']??'..'}}" class="card-img-top" alt="...">
              <div class="card-body">
                <h5 class="card-title">{{ $site['primaryName'] }}</h5>
                  <ul class="list-inline">
                  
                    <p class="card-text">
                        <strong>Address: </strong>{{ $site['address']}}
                        <strong>Rating:</strong> 
                        <span class="text-warning">
                          @for($start = 0; $start< $site['stars']; $start++ )
                          &#9733;
                          @endfor
                        </span> <!-- 4-star rating -->
                    </p>
                    <strong>Facilities: </strong>  
                    @foreach( $site['facilities'] as $val)
                      <li class="list-inline-item">{{$val}}.</li> 
                    @endforeach

                  </ul>
              </div>
              <div class="card-footer">
                <small  class="text-body-secondary"> 
                  <strong class="text-wrap">Phone: </strong>{{ $site['phone']}}</br>
                  <strong>Email: </strong>{{ $site['notificationEmail']}}
                </small>
              </div>
            </div>
          @endforeach
        </div>
        <!-- End Top 3 Hotels Section -->
      </section>
      <section>
        <h2 class="text-center pt-5">
          Interactive Chart:
          <small class="text-body-secondary">Explore Your Booking Options Through Visualization</small>
        </h2>
        <p class="lead"> Use our interactive chart to effortlessly visualize hotel prices and availability. Quickly compare options for informed booking decisions, ensuring you find the perfect stay for your needs and budget.</P>

        <!-- Data filter Section -->
        <div class="row pt-3">
          <div class="col-6">
            <div class="card text-bg-light mb-3">
              <div class="card-header">Choose Your Preferred Days or week</div>
              <div class="card-body">
                <div class="row">

                  <div class="col">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="days" value="DAYS_7">
                        <label class="form-check-label" for="gridWeek">
                          Week
                        </label>
                      </div>
                  </div>
                  
                  <div class="col">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="days" value="DAYS_14">
                      <label class="form-check-label" for="gridTwoWeek">
                        Two weeks
                      </label>
                    </div>
                  </div>

                  <div class="col">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="days" value="DAYS_30">
                      <label class="form-check-label" for="gridMonth">
                        30 days
                      </label>
                    </div>
                  </div>

                  <div class="col">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="days" value="DAYS_60" checked>
                      <label class="form-check-label" for="gridTwoMonths">
                        60 days
                      </label>
                    </div> 
                  </div>
                </div>
              </div>
            </div>      
          </div>
            
          <div class="col">

            <div class="card text-bg-light mb-3">
              <div class="card-header">Currency</div>
              <div class="card-body">
                
                <select id="currency" class="form-control">
                  @foreach ( $currencies as $code => $label )
                    <option value="{{ $code }}" {{ $defaultCurrency === $code ? 'selected' : '' }}>
                        {{ $code }}
                    </option>
                  @endforeach
                </select> 

              </div>
            </div>

          </div>

          <div class="col">

            <div class="card text-bg-light mb-3" style="max-width: 18rem;">
              <div class="card-header">Choose Your Accommodation</div>
              <div class="card-body">
                
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="pr1-pr2" value="pr1" checked>
                  <label class="form-check-label" for="gridPr1">
                    The price for 1 adult.
                  </label>
                </div>

                <div class="form-check">
                  <input class="form-check-input" type="radio" name="pr1-pr2" value="pr2">
                  <label class="form-check-label" for="gridTwoPr2">
                    The price for 2 adults.
                  </label>
                </div>

              </div>
            </div>
          </div>
        </div>   
        <!-- End Data filter Section -->

        <div class="row">

          <!-- Loading Spinner (Initially hidden) -->
          <div id="loadingSpinner" class="text-center mt-4 " style="display: none;">
              <div class="spinner-border text-primary" role="status">
                  <span class="sr-only">Loading...</span>
              </div>
              <p>Please wait while we fetch the data...</p>
          </div>
          <!-- End Loading Spinner (Initially hidden) -->

          <!-- Div that will hold the line chart -->
          <div id="google_chart" style="width: 100%; height: 500px;  background: #f0f0f0;"class="rounded border"></div>
          <!-- End Div that will hold the line char -->
        </div>
        @else
        <div class="alert alert-warning" role="alert">
            {{$error}}
        </div>
        @endif
      </section>
    </div>
  </main>
     <!-- Footer Section -->
    <footer class="bg-light text-black text-center py-3 mt-4 rounded border">
        <p class="mb-0">&copy;  2024 The Access Group. All Rights Reserved.</p>
        <p>Made with ❤️ by <a target="_blank" href="https://www.linkedin.com/in/cardenas-g-ismael-77682129a/" class="text-black">Byte Me</a></p>
    </footer>
    <!--End Footer Section -->
    </div>
    
  </body>
</html>
