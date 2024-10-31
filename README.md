# Hotel Graph Visualization Project

In this project, users are able to examine the pricing data of the top three hotels in Ireland by using an interactive chart that allows them to compare prices over a specified period.

## Table of Contents

- [Installation](#installation)
- [Running the Application](#running-the-application)
- [Usage](#usage) 

## Installation

### Step 1: Clone the Repository

First, clone the repository to your local machine using the following command:

```bash
git clone https://github.com/IsmlCg/hotel-graph-visualization.git
cd hotel-graph-visualization
```
### Step 2: Install Dependencies
Make sure Composer is installed. Run the following command to install the necessary PHP dependencies:

```bash
composer install
```
### Step 3: Set Up Environment Variables
Make a copy of the .env.example file and rename it to .env. Open the .env file and set the following variables:

```bash
API_URL=https://api.example.com/hotels
AVVIO_API_URL=https://api.example.com/hotels
AVVIO_USER_NAME=your_username
AVVIO_PASSWORD=your_password
```
### Step 4: Generate Application Key
Run the following command to create a new application key:

```bach
php artisan key:generate
```
## Run the application 
After configuring the environment, you can run the application locally. Use the following command:

## Usage 
Once you launch the app, you will be able to see the interactive chart displaying the pricing information of the top three hotels. Users can select different options to visualize the data effectively.