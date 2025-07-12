<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xi-TEK Services</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans text-gray-800">
    <div class="min-h-screen">
        <!-- Main Content -->
        <div class="container mx-auto px-4 py-6">
            <!-- Navigation Pills -->
            <div class="flex flex-wrap gap-2 md:gap-4 justify-center mb-8">
                <button class="service-nav-btn flex items-center p-3 rounded-full bg-white shadow-md hover:shadow-lg transition-all duration-300 text-white bg-red-900 bg-opacity-95 shadow-md -translate-y-1" data-service="embedded-electronics">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                    </svg>
                    <span class="ml-2 hidden md:inline">Embedded Electronics</span>
                </button>
                
                <button class="service-nav-btn flex items-center p-3 rounded-full bg-white shadow-md hover:shadow-lg transition-all duration-300" data-service="web-design">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9" />
                    </svg>
                    <span class="ml-2 hidden md:inline">Web Design</span>
                </button>
                
                <button class="service-nav-btn flex items-center p-3 rounded-full bg-white shadow-md hover:shadow-lg transition-all duration-300" data-service="robotic-design">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span class="ml-2 hidden md:inline">Robotics</span>
                </button>
                
                <button class="service-nav-btn flex items-center p-3 rounded-full bg-white shadow-md hover:shadow-lg transition-all duration-300" data-service="electrical-installation">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span class="ml-2 hidden md:inline">Electrical</span>
                </button>
                
                <button class="service-nav-btn flex items-center p-3 rounded-full bg-white shadow-md hover:shadow-lg transition-all duration-300" data-service="security-systems">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span class="ml-2 hidden md:inline">Security</span>
                </button>
                
                <button class="service-nav-btn flex items-center p-3 rounded-full bg-white shadow-md hover:shadow-lg transition-all duration-300" data-service="selling-electronics">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <span class="ml-2 hidden md:inline">Shop</span>
                </button>
            </div>

            <!-- Service Content Sections -->
            <div id="service-content-container">
                <!-- Embedded Electronics Section (Default shown) -->
                <section id="embedded-electronics-content" class="service-section bg-cover bg-center relative rounded-xl overflow-hidden mb-8" style="background-image: url('/api/placeholder/1200/800')">
                @include('services.embeded')
                </section>

                <!-- Web Design Section (Hidden initially) -->
                <section id="web-design-content" class="service-section bg-cover bg-center relative rounded-xl overflow-hidden mb-8 hidden" style="background-image: url('/api/placeholder/1200/800')">
                  @include('services.webdesign')  
                </section>

                <!-- Robotic Design Section (Hidden initially) -->
                <section id="robotic-design-content" class="service-section bg-cover bg-center relative rounded-xl overflow-hidden mb-8 hidden" style="background-image: url('/api/placeholder/1200/800')">
                    @include('services.robotics')
                </section>

                <!-- Electrical Installation Section (Hidden initially) -->
                <section id="electrical-installation-content" class="service-section bg-cover bg-center relative rounded-xl overflow-hidden mb-8 hidden" style="background-image: url('/api/placeholder/1200/800')">
                   @include('services.electrical')
                </section>

                <!-- Security Systems Section (Hidden initially) -->
                <section id="security-systems-content" class="service-section bg-cover bg-center relative rounded-xl overflow-hidden mb-8 hidden" style="background-image: url('/api/placeholder/1200/800')">
                    @include('services.security')
                </section>

                <!-- Selling Electronics Section (Hidden initially) -->
                <section id="selling-electronics-content" class="service-section bg-cover bg-center relative rounded-xl overflow-hidden mb-8 hidden" style="background-image: url('/api/placeholder/1200/800')">
                  @include('services.shop')
                </section>
            </div>
        </div>

        <!-- JavaScript for Service Navigation -->

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const serviceButtons = document.querySelectorAll('.service-nav-btn');
                const serviceSections = document.querySelectorAll('.service-section');
            
                function showService(serviceId) {
                    serviceSections.forEach(section => section.classList.add('hidden'));
                    const selectedSection = document.getElementById(serviceId + '-content');
                    if (selectedSection) selectedSection.classList.remove('hidden');
            
                    serviceButtons.forEach(button => {
                        button.classList.remove(
                            'bg-red-900', 'bg-opacity-95', 'text-white', '-translate-y-1'
                        );
                        button.classList.add('bg-white', 'text-gray-800');
            
                        if (button.dataset.service === serviceId) {
                            button.classList.add(
                                'bg-red-900', 'bg-opacity-95', 'text-white', '-translate-y-1'
                            );
                            button.classList.remove('bg-white', 'text-gray-800');
                        }
                    });
                }
            
                serviceButtons.forEach(button => {
                    button.addEventListener('click', function () {
                        const serviceId = this.dataset.service;
                        showService(serviceId);
                    });
                });
            
                showService('embedded-electronics');
            });
            </script>
            
</div>
</body>
</html>