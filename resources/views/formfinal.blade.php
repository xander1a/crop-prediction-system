@include('layouts.app')

@section('title', 'Market Overview')
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Crop Price Prediction</h1>
                <p class="text-gray-600">Enter market data to predict crop prices</p>
            </div>

            @if(session('error'))
                <div class="bg-red-100 p-2 text-red-700 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Error Message (if exists) -->
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 hidden" id="error-message">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">Error message would appear here</p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="bg-white shadow-xl rounded-lg overflow-hidden">
                <form class="p-6 md:p-8" action="{{ route('crop.predict') }}" method="POST">
    @csrf
                    <!-- Location Information -->
                    <div class="mb-8">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Location Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Admin1 (Province)</label>
                                <select name="admin1" id="admin1" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required>
                                    <option value="">Select Province</option>
                                    <option value="Kigali City">Kigali City</option>
                                    <option value="Eastern Province">Eastern Province</option>
                                    <option value="Northern Province">Northern Province</option>
                                    <option value="Southern Province">Southern Province</option>
                                    <option value="Western Province">Western Province</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Admin2 (District)</label>
                                <select name="admin2" id="admin2" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required>
                                    <option value="">Select District</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Market Name</label>
                                <select name="market" id="market" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required>
                                    <option value="">Select Market</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Market ID</label>
                                <input type="number" name="market_id" placeholder="e.g., 1076" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required>
                            </div>
                            
                        </div>
                    </div>

                    <!-- Commodity Information -->
                    <div class="mb-8">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            Commodity Details
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Category</label>
                                <select name="category" id="category" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required>
                                    <option value="">Select Category</option>
                                    <option value="cereals and tubers">Cereals and Tubers</option>
                                    <option value="pulses and nuts">Pulses and Nuts</option>
                                    <option value="vegetables and fruits">Vegetables and Fruits</option>
                                    <option value="non-food">Non-Food</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Commodity Name</label>
                                <select name="commodity" id="commodity" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required>
                                    <option value="">Select Commodity</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Commodity ID</label>
                                <input type="number" name="commodity_id" placeholder="e.g., 51" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Price Type</label>
                                <select name="pricetype" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required>
                                    <option value="">Select Price Type</option>
                                    <option value="Wholesale">Wholesale</option>
                                    <option value="Retail">Retail</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Currency</label>
                                <select name="currency" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required>
                                    <option value="RWF">RWF (Rwandan Franc)</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Unit</label>
                                <select name="unit" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required>
                                    <option value="">Select Unit</option>
                                    <option value="KG">KG (Kilogram)</option>
                                    <option value="Sack">Sack</option>
                                </select>
                            </div>
                            <!-- TARGET DATE SELECTOR -->
                            <div class="space-y-1 col-span-3">
                                <label class="block text-sm font-medium text-gray-700">Target Prediction Date</label>
                                <input type="date" name="target_date" min="2008-04-15" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required>
                                <p class="text-xs text-gray-500 mt-1">Select a future date for price prediction</p>
                            </div>
                        </div>
                    </div>

                    

                    <!-- Submit Button -->
                    <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200">
                        <button type="submit" 
                                class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-medium py-3 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 shadow-lg hover:shadow-xl">
                            <span class="flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                Predict Crop Price
                            </span>
                        </button>
                        <button type="reset" 
                                class="flex-1 sm:flex-none bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-3 px-6 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                            Reset Form
                        </button>
                    </div>
                </form>
            </div>

            <!-- Info Card -->
            <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">How it works</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>This prediction model uses historical price data, location information, and commodity details to forecast future crop prices. Fill in all fields with accurate data for the best prediction results.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Data mappings for Rwanda provinces, districts, and markets
        const locationData = {
            "Kigali City": {
                districts: ["Nyarugenge", "Gasabo", "Kicukiro"],
                markets: {
                    "Nyarugenge": ["Kigali", "Kimisagara"],
                    "Gasabo": ["Kimironko"],
                    "Kicukiro": ["Kicukiro"]
                }
            },
            "Eastern Province": {
                districts: ["Bugesera", "Gatsibo", "Kayonza", "Kirehe", "Ngoma", "Nyagatare", "Rwamagana"],
                markets: {
                    "Bugesera": ["Ruhuha"],
                    "Gatsibo": ["Kiramuruzi"],
                    "Kayonza": ["Rwagitima"],
                    "Kirehe": ["Kabarondo"],
                    "Ngoma": ["Nyakarambi", "Kibungo"],
                    "Nyagatare": ["Nyagatare"],
                    "Rwamagana": ["Musha"]
                }
            },
            "Northern Province": {
                districts: ["Gakenke", "Gicumbi", "Musanze", "Rulindo"],
                markets: {
                    "Gakenke": ["Gakenke"],
                    "Gicumbi": ["Base"],
                    "Musanze": [],
                    "Rulindo": []
                }
            },
            "Southern Province": {
                districts: ["Gisagara", "Huye", "Nyamagabe", "Nyanza", "Ruhango", "Nyaruguru", "Muhanga"],
                markets: {
                    "Gisagara": [],
                    "Huye": [],
                    "Nyamagabe": [],
                    "Nyanza": [],
                    "Ruhango": [],
                    "Nyaruguru": [],
                    "Muhanga": []
                }
            },
            "Western Province": {
                districts: ["Ngororero", "Nyabihu", "Nyamasheke", "Rubavu", "Rusizi", "Rutsiro", "Karongi"],
                markets: {
                    "Ngororero": [],
                    "Nyabihu": [],
                    "Nyamasheke": [],
                    "Rubavu": [],
                    "Rusizi": [],
                    "Rutsiro": [],
                    "Karongi": []
                }
            }
        };

        // Commodity data mappings
        const commodityData = {
            "cereals and tubers": ["Maize", "Rice", "Cassava flour", "Potatoes (Irish)", "Sorghum", "Sweet potatoes", "Cassava"],
            "pulses and nuts": ["Beans", "Beans (dry)"],
            "vegetables and fruits": ["Eggplants", "Passion fruit"],
            "non-food": ["Charcoal"]
        };

        // Event listeners
        document.getElementById('admin1').addEventListener('change', function() {
            const province = this.value;
            const districtSelect = document.getElementById('admin2');
            const marketSelect = document.getElementById('market');
            
            // Clear and reset district dropdown
            districtSelect.innerHTML = '<option value="">Select District</option>';
            marketSelect.innerHTML = '<option value="">Select Market</option>';
            
            if (province && locationData[province]) {
                const districts = locationData[province].districts;
                districts.forEach(district => {
                    const option = document.createElement('option');
                    option.value = district;
                    option.textContent = district;
                    districtSelect.appendChild(option);
                });
            }
        });

        document.getElementById('admin2').addEventListener('change', function() {
            const province = document.getElementById('admin1').value;
            const district = this.value;
            const marketSelect = document.getElementById('market');
            
            // Clear market dropdown
            marketSelect.innerHTML = '<option value="">Select Market</option>';
            
            if (province && district && locationData[province] && locationData[province].markets[district]) {
                const markets = locationData[province].markets[district];
                markets.forEach(market => {
                    const option = document.createElement('option');
                    option.value = market;
                    option.textContent = market;
                    marketSelect.appendChild(option);
                });
            }
        });

        document.getElementById('category').addEventListener('change', function() {
            const category = this.value;
            const commoditySelect = document.getElementById('commodity');
            
            // Clear commodity dropdown
            commoditySelect.innerHTML = '<option value="">Select Commodity</option>';
            
            if (category && commodityData[category]) {
                const commodities = commodityData[category];
                commodities.forEach(commodity => {
                    const option = document.createElement('option');
                    option.value = commodity;
                    option.textContent = commodity;
                    commoditySelect.appendChild(option);
                });
            }
        });
    </script>