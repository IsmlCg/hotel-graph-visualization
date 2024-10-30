<!DOCTYPE html>
<html>
  <head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

            const data = @json($data);
            let datePrices =[ data.header,...data.body ];
            let currentCurrency = @json( $defaultCurrency );

            $('#currency').change(function() {
                loadData();
                currentCurrency = $( this ).val(); 
            });

            $('input[name="days"]').click(function() {
                loadData(); 
            });

            $('input[name="pr1-pr2"]').click(function() {
                loadData(); 
            });
            
            google.charts.load('current', {'packages':['corechart']});
            google.charts.setOnLoadCallback(drawChart);

            function drawChart() {
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
              const chart = new google.visualization.LineChart(document.getElementById('google_chart'));
              chart.draw(data, options);
            }

            function onLoadData_Error ( error )
            {
              $('#loadingSpinner').show();
              $('#google_chart').show();  
              alert( 'Error loading data. ' ); 
            }
            function onLoadData_Success ( data )
            {
              datePrices = data ??[]; 
              drawChart(); 
              updateAlert();
            }

            function fetchData( url, method, body, successCallback , errorCallback   ) {
              const options = {
                method :method,
                headers: {
                  'Content-Type': 'application/json', // Set content type to JSON
                  'X-CSRF-TOKEN': '{{ csrf_token() }}' // Include CSRF token for Laravel
                },
                body: body ? JSON.stringify(body) : null, // Convert body to JSON string if it exists
              };
              fetch( url, options )
                  .then(response => {
                      if (!response.ok) {
                          throw new Error('Network response was not ok ' + response.statusText);
                      }
                      return response.json(); // Parse the JSON response
                  })
                  .then(data => {
                    successCallback( data ); // Pass the data to the callback on success
                  })
                  .catch(error => {
                    errorCallback(error ); // Pass the error to the callback if there was a problem
                  });
            }

            function loadData(){

              $('#loadingSpinner').show();
              const days = $('input[name="days"]:checked').val();
              const toCurrency = $('#currency').val();
              const pr1OrPr2 = $('input[name="pr1-pr2"]:checked').val();
              const body = {
                days: days,
                toCurrency: toCurrency,
                pr1OrPr2 : pr1OrPr2
              }; 

              fetchData(
                '{{ route('hotels.data') }}',
                'POST', // HTTP method
                body, // Body content as an object
                onLoadData_Success,
                onLoadData_Error
              );
            }

            function updateAlert(){
              $('#loadingSpinner').hide();
              $('#google_chart').show();  
            }
        });
        
    </script>
  </head>

  <body>
  
    <div class="container mt-5">
 
      <h1 class="text-center p-4">
        Stay in Style:
        <small class="text-body-secondary">The Top 3 Hotels for an Unforgettable Experience</small>
      </h1>

      <!-- Top 3 Hotels Section -->
      <div class="card-group">
        @foreach ( $data['propertyDetails']["siteList"] as $site )
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

      <h2 class="text-center pt-5">
        Interactive Chart:
        <small class="text-body-secondary">Explore Your Booking Options Through Visualization</small>
      </h2>
      
      <!-- Data filter Section -->
      <div class="row pt-3">
        <div class="col-6">
          <div class="card text-bg-light mb-3">
            <div class="card-header">Choose Your Preferred Day or week</div>
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
        <div id="loadingSpinner" class="text-center mt-4" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p>Please wait while we fetch the data...</p>
        </div>
        <!-- End Loading Spinner (Initially hidden) -->

        <!-- Div that will hold the line chart -->
        <div id="google_chart" style="width: 100%; height: 500px;"class="rounded-3"></div>
        <!-- End Div that will hold the line char -->
      </div>
    </div>
     <!-- Footer Section -->
    <footer class="bg-dark text-white text-center py-3 mt-4">
        <p class="mb-0">&copy;  2024 The Access Group. All Rights Reserved.</p>
        <p>Made with ❤️ by <a href="#" class="text-white">Byte Me</a></p>
    </footer>
    
  </body>
</html>
