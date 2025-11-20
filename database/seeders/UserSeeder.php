<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin users
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '+1234567890',
            'address' => '123 Admin Street, New York, NY 10001',
        ]);

        // Vendor users
        $vendors = [
            [
                'name' => 'TechGear Store',
                'email' => 'vendor1@example.com',
                'phone' => '+1234567891',
                'address' => '456 Vendor Ave, San Francisco, CA 94102',
            ],
            [
                'name' => 'Fashion Hub',
                'email' => 'vendor2@example.com',
                'phone' => '+1234567892',
                'address' => '789 Fashion Blvd, Los Angeles, CA 90001',
            ],
            [
                'name' => 'Home Essentials',
                'email' => 'vendor3@example.com',
                'phone' => '+1234567893',
                'address' => '321 Home Street, Chicago, IL 60601',
            ],
        ];

        foreach ($vendors as $vendor) {
            User::create([
                'name' => $vendor['name'],
                'email' => $vendor['email'],
                'password' => Hash::make('password'),
                'role' => 'vendor',
                'phone' => $vendor['phone'],
                'address' => $vendor['address'],
            ]);
        }

        // Customer users
        $customers = [
            [
                'name' => 'John Smith',
                'email' => 'john@example.com',
                'phone' => '+1234567894',
                'address' => '111 Customer Lane, Boston, MA 02101',
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah@example.com',
                'phone' => '+1234567895',
                'address' => '222 Buyer Street, Seattle, WA 98101',
            ],
            [
                'name' => 'Michael Brown',
                'email' => 'michael@example.com',
                'phone' => '+1234567896',
                'address' => '333 Shopper Ave, Miami, FL 33101',
            ],
            [
                'name' => 'Emily Davis',
                'email' => 'emily@example.com',
                'phone' => '+1234567897',
                'address' => '444 Consumer Blvd, Denver, CO 80201',
            ],
            [
                'name' => 'David Wilson',
                'email' => 'david@example.com',
                'phone' => '+1234567898',
                'address' => '555 Client Street, Austin, TX 78701',
            ],
        ];

        foreach ($customers as $customer) {
            User::create([
                'name' => $customer['name'],
                'email' => $customer['email'],
                'password' => Hash::make('password'),
                'role' => 'customer',
                'phone' => $customer['phone'],
                'address' => $customer['address'],
            ]);
        }
    }
}
