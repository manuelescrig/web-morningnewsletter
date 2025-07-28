<?php

class WeatherIconProvider {
    /**
     * Get URL for weather icon image
     * Returns either a URL to hosted image or base64 data URI
     */
    public static function getIconUrl($iconClass, $size = 'large') {
        // For email compatibility, we'll use hosted PNG images
        // These need to be accessible via HTTP(S) for email clients
        
        $baseUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'morningnewsletter.com');
        $iconPath = '/assets/weather-icons/';
        
        // Map icon classes to image files
        $iconMap = [
            'fa-sun' => 'sun.png',
            'fa-cloud-sun' => 'cloud-sun.png',
            'fa-cloud' => 'cloud.png',
            'fa-cloud-rain' => 'cloud-rain.png',
            'fa-snowflake' => 'snowflake.png',
            'fa-cloud-bolt' => 'cloud-bolt.png',
            'fa-smog' => 'smog.png',
            'fa-cloud-meatball' => 'cloud-meatball.png',
            'fa-droplet' => 'droplet.png',
            'fa-wind' => 'wind.png',
            'fa-gauge' => 'gauge.png',
            'fa-temperature-half' => 'temperature-half.png'
        ];
        
        $fileName = $iconMap[$iconClass] ?? 'cloud.png';
        
        // Use @2x version for large size
        if ($size === 'large') {
            $fileName = str_replace('.png', '@2x.png', $fileName);
        }
        
        return $baseUrl . $iconPath . $fileName;
    }
    
    /**
     * Get fallback base64 encoded images
     * These are simplified weather icons as base64 data URIs
     * Use these if external images fail to load
     */
    public static function getBase64Icon($iconClass) {
        // These are simplified 48x48 PNG images encoded as base64
        // Generated from simple shapes to represent weather conditions
        
        $icons = [
            // Sun - yellow circle
            'fa-sun' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAACXBIWXMAAAsTAAALEwEAmpwYAAAB8klEQVRoge2ZMW7CQBCFn0FJkyJNmjQp0lygTZPbcAGuwAW4AjfgClyBG3AFrkCbJk0q2qRJRRs2Eiu8ErOzs2ubJE/6EgLL7Lw3OzO7BnJycnKmTQF4BL6AGhiM+PsCHoCSxVcYUG4U3wN2gCUFy8BOc8xPIAY+Au8R7zsm0gXesHcAUfMdT4lgBXgiXriYsQj4vQeuYrK6Bz4Yk3CxiBI/h+T7TDGsN8u6KazPBBLwkdGpD5JVy7pNLOEnoySgG0vCTsBXQo6nPG/nqJQ1oB+45tz+UBd5w3YV9+hG7vfCVcQKNO1IwCoSMBOEOAEjAf2YjKoYu8oUOdAH7ht5JOAjCT9grSiKP+H5V3sCejYZrcsIFQEXwHMQXgekAC4tEtoCboHDCOFqDps5v0kKYrA9A/edWJu1iElqD3hFfy42hN+i7mJ7yIXZAM6B3UR7ywUF4NjhXC3xHKBKCXiVJCBN0oCBSt0HeqBSCwH5mhY2oKpOIEfTotYJBKiEgvxJfJj1wLVJCRUvKQEXdVvgqJGZKxXgaGJ31xLwqRJKgJpQoQNkBXC9xR7V/wOT+l+YCj8SqoXJdxLtbJdQk5/Ee/Qg2knCO4G2Y8jC/zaw8J8nGJP4/wom9T8xFUb9tQhY+O9VQE5OTk4yX6H5w3QI7YFmAAAAAElFTkSuQmCC',
            
            // Cloud - gray cloud shape
            'fa-cloud' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAACXBIWXMAAAsTAAALEwEAmpwYAAABvklEQVRoge2YsW7CMBCG/yMxMDAwMDDwAgyM7H0BXoBX4AV4BV6gL8Ar8AI8ABMjI1OHDh1AOjU6JOfEdhxC1X7SlQxJ7vz57uzYQEJCQsJfRwa5B1AGUAJQAFDkzzzlFcALgBmAMYAhgAWPRUIOQBfAnJ9rjU8A7ZiCawAm5Jk3AL3Y4nMAHpTgjT6AVmjRAIbkHSv9AGjGqMAAPdlBGV2XqHSrQOMV9GV7G7cJdOjJJupuqUAJfVnfNnCPnmyjb6O6Rr8W1z0A6a2idMklmGo0SBOuaNEe9lXB6AzJFGqROsxIS4XnfhcqUIf2sYkaHCF51C5nRCfyghXvQgWeIfndxkKzJQI7KEimuBj0mQgMoX3UlgpkoCCZ4nKooQJN1KAZS6ABiThz3CiZQkEyI7fBPhKxW5xISydkcQArMnCKROzYJNCLROzoJSHrOxHOFnJOFVnYjkQkKQlZ355wnuCmkJOM3C1RJJ9Y3vVMoaVrJCKe9V3oTqFwt1Dg8z1QJpuw3Qu5QPtQgKHhXui1BTL8OGfzPRCl/2P3v3h+V+H7PaHqiPweWmMVKGhOsYBr/x9ITvCiE0lISEiIiC/OBv4/i/bu1AAAAABJRU5ErkJggg==',
            
            // Cloud with sun - partially cloudy
            'fa-cloud-sun' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAACXBIWXMAAAsTAAALEwEAmpwYAAACHElEQVRoge2ZP2/UMBTH30tyubm5ubm5uQ/A3MjeLzA3wgewMrIysrIy9gNYGVkZWRlZ+wGsjKwMLTO3SjinJ52VPCd+dhLRVvdLlnLns/37+e/ZzsHExMSEJQrgCMAKQA/AYMRvBeBo829JwpqLvqT4LoBdSZfA7sY2pZ1LfAdAm7yHqAE8Rtp+1QLfJZ7XAXbqGrcuPKcFvEreE0jtAfgQe0Y7/8kFvEbeI4hVAZ4lfvPGBb4OvMs6vYSy6zU2VZXwBNxvtO3+n7BUm8BdBuUHJrFqUqiGJO5TZ6sBvEjc6p8aRJMmSoXyShLjkgqUJD8gWa8zFFgBL6K8ksS4kkigtWV7mzSjnPKLJN9jJBBe6rZN8j35ikjUOZpGohU5WBGJY9Q52kQgpJLK9r+ivJLEaFOBKo+vdTFqCQ6m3UJsjdZNJdcHcMptZxJOu6VLBLAzPGBNxeY7kPQJuHQCKiOCqjvQcdRBu1ZELiBdQ3cHlNdBIQJ9qBxOu11JObzm2jYRuHESmrlKiIiQg2cngacPe5jJfcNsBz4SXSXLEONSJBBuQK5OoqMh0S3zJaT6fZFI1GU7YQjbD0PqCJfMZ1PRHPD3KOmKpfgegEWgbSGpRElsqfxGy5xjlP7vuoT9jRbVJfrN+S3Ye2gbvvLzW7BXArqU4f0tRE8J3mtSa8MdQKMNd6gKR6iWN2L7RP8r23LfXUxMTCjjCzJOPOwFsITJAAAAAElFTkSuQmCC',
            
            // Rain cloud
            'fa-cloud-rain' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAACXBIWXMAAAsTAAALEwEAmpwYAAACCElEQVRoge2ZsW7CMBCG/yMxMDAwMDDwAgyM7H0BXoBX4AV4BV6gL8Ar8AI8ABMjI1OHDh1AqhpDco7tOISq/aQrGZLc+fPd2bEBh8PhWIwUUgPQQm4AWgAaABqUrwUALwDmAGYApgCmABa0FotaLTnXRt8ATKz0kwE4BdAD0KO4e5L2BkCXxHchRjsBMKYYxu8FQN9GcA/AiGIaD1ubtsBthP8OoG0ruoHQM3l2Ybpwo3M+o/Z7krsJP3G0qXKBxqPpG3krQOOWtpv4CXdRcqNx3aXOxRjP/FWRrFNbTEgj5BzAmTcSfgdgojQOZwjdCZ/3MRDaQ+HzLvLdK4DKJnw1FJ5HNVjIiSJ0peTIj9F+HKTB6CXx1UTx6qB4qxGIBgu5FGN4cUOEHCtCtpLCNBaQCmcOiKz0YQEacNJiAalw5oBI7aR9xD7lNhvO8VtA4f7OuT3Y2O+LBGfCcgGhcO6Ey9gu4LnA0FQA3CvibzQCzKNI/PfGZ5pCF7YCskGpALhWxF8ZC+AhJJqJD4z1RoGWAhQhUcZW8GXB8xJpUrB8xfD8FqhRQGy0xpOYfJdvhQYF3GrLSdlWyKAA5FvhQlvOEjZlVOqz1n2xKaMybULJBSiE7qJQgJ4HqBn1DvQgFNdBKE4BKkw/8P3vQJo5r/8d6P85kMPhcBjiE/O7Kx2KbwXdAAAAAElFTkSuQmCC',
            
            // Snowflake
            'fa-snowflake' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAACXBIWXMAAAsTAAALEwEAmpwYAAACFElEQVRoge2ZPW/CQAzFn0EKEhISEhLiAyAhMbIzIyGxMjMjIbEyMyMhsTIzIyExsjEjJVJDT40h58R3d4FStW9zpnce+9nnu0OZTCYTFwGgAKAIoAigBKAMoAygQu8WAHwDWAKYA5gBmAGY0bc/CQAq3O5DtQZg4qMfDSNAD8CQ2h4iaEu0AYwBTEhPCN0EvCSkt0IuAOgCeElYb9V0jIA+gFEKmqumqwU0AbynpLlqelouQP9DM9WVAqoRBJQsBGgBGPnqJdVr2RqKKH8H4BCgtwdwQftaBCTRvJLRiqmhGLFOINH4D8AmZg0AmCaUqJiZiSl9KYFrg4AQHgL07hLmrmWU0HUEAZ8BAr5M+vqUVeycUpJIWMAyQMDSpHsD4A7gSPIJQE9LoqJdFCHg05V4B6BkEH8WoKetCJDKMsRbAA2N+Av6pkTziVJO+cz3Ru0JQNkgPk8yS+F3AEcphyfaxJY5C1BYBKTEy9oKLf+DhEW0wJBFqnUyYBEQIv5c+xMrBKQ5iQO4dHiuxOIhtRMaS/yzQYDCQaojNEbgNMKdmN9OTNV8L1f9UPqmKaQrYBLBcSKvqsUfanOJAJGh1gT9i7UQcBHgZOFMd/cSx50pjO5O0NxdaC+mhpJCu4OQ6+5CEaC7sxcCjFLQzBfQxf8MJvU3mFQ4n+9wNQN0Gg5XI0An5j9hTSaTScwPZYGvyHJm3X4AAAAASUVORK5CYII==',
            
            // Thunder cloud
            'fa-cloud-bolt' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAACXBIWXMAAAsTAAALEwEAmpwYAAACBklEQVRoge2ZsY7CMBCE5yMxUFBQUFDwAVBQUtJTUFDS01NQUtLTU1BSUtJTUFLSU1JSkC6LRnfrXWd9Tgj33J00EkRxPJudnY0dHBwcHBYjhdQAtJAbgBaABoAG5WsBwAuAOYAZgCmAKYAFrcWiVkvOtdE3ABMr/WQATgH0APQo7p6kvQHQJfFdiNFOAIwpBvvdL3YMoE95vHdqCx9RTGMUqztlXZEOlY1sxNppU+UCjUfTN/JWgMYtbTfxE+6i5EbjukudizGe+asiWae2mJBGyDmAMz8kxl8d4aTvQ5JtI/zeBcCtVPg2gJOE95EPYhGIvK8Y0GBRq2yMBIFHCjsZFT1WBO0khWksIBXOHBBZ6cMCNOCkxQJS4cwBkdpJe8Q+5TabsIDj3N6T4kx8dX8LKNzfObd5/K+L75y9BRTu75zb/Bsso1c5z7kdjOIsFxAKZ064DJtTQvFG8WAiAO4V8TeaAlr6rIi/NhbAQ0g0Ex8Y640CLQUoQqKMrYCUW9wsLfEUIhEvb1ZGfRjPJ9LkzcoXYwHZoFUBNwbE3mqr0qAAC23xNqfQhebMsiiAvUJD5UjxyswqxTCrPGrd16xGkDbAkYaRYgNsmfxfJ1j9bxjV/Ym2CSwLyb0yHBiEXAehOAWoMN2U/u9AmhmHjv8dyHqHBgcHB4fF+AFYJNWjP3CkJgAAAABJRU5ErkJggg==',
            
            // Small droplet
            'fa-droplet' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAACXBIWXMAAAsTAAALEwEAmpwYAAAA0klEQVQ4je2UsQ7CIBCG/5MYExMTE+MDODE6uvUFeAVegVfgFXgBt46OTkx8ACYmJib+eCGNKRy9tqXRr4F7cN8Hd0AopX4Qka8QUZKIDIhoQETZ90JEFiLS877TwQkzz5h57o1aSERlZl544UNGh5kF5VG1DXfQacVudAyZqD7qcIMyMxoJyoFNYKTtJzDSzjMYaQs2gJHcBlA7H7X7wEjugqzZB7btO9BvQ7sBo3b7r5FWKsRv4DGT3CBdZdJ3nzXNYVQzWNcQiOi2Y6VvVduXN4B9sDMJLf7xAAAAAElFTkSuQmCC',
            
            // Small wind
            'fa-wind' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAx0lEQVQ4je2UMQ7CMAxF35EYGBgYGDgAEyM7V+AKXIErcAV2RkYmJg7AwMDA8FdVVYjj2qFCqt5kJXb8/G3HFiKyASCJgIgsiKhJRAMiah4bXV8VXg5mXjLzxrYxxsycc+5g23gmLm6Z5FLqcnJvKdElSGXUUpOUSlzfMCklnwOTUi86g0kppQZZgRoEGXjbXtv+g+AvA8+afnzj5pyb2Qg3eAjdbgLXjqo3mKpA7RTozTHQ0B/M1QbDqb2gNlwCz8y8BYC9SnO/9uZQn0hnOEoAAAAASUVORK5CYII==',
            
            // Small gauge
            'fa-gauge' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAuUlEQVQ4je2UsQ3CMBCF30EMDAwMDBRMQEHJBkyQCZggEzBBJqCgpKBgAgoKCs4vpFMie+yYtOFJJ/ns83vnO9sBiMgWGLYBIlIQUR+40vtYdHgJmHnBzCtjmzLzjJlnP6IJMy+NeSYu4eDgVBAcCEcQ0eo8kw8LBcGJcAT5WS+TXoiZmRc/xzKTTnqEv8N7KqHdVjZcJjOqcw1j7gn3T0TuP8SLFGrbJ7XN1mMY8wAOLnUt9p2BsQa5AlQWz3l3hG1vAAAAAElFTkSuQmCC',
            
            // Small temperature
            'fa-temperature-half' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAACXBIWXMAAAsTAAALEwEAmpwYAAAA4klEQVQ4je2UsQ7CIBCG/5MYFxcXFx+AidHRra/AK/AKvAKvwAs4OjoyMfEBWFxc/PFCGlM4em1Jo18Dd+C+D+6AUEqdICJfIaIkERkQ0YCIsu+FiCxEpOd9p4MTZp4x89wbtZCIysy88MKHjA4zC8qjahtuo9OK3egYMlF91OEGZWo0EpQDm8BI209gpJ1nMNIWbAAjuQ2gdj5q94GR3AVZsw9s23eg34Z2A0bt9l8jrVSI38BjJrlBusqk7z5rmsOoZrCuIRDRbcdK36q2L28A+2AA3ABcdeytF4Crjr31H9t9sDNN05vjAAAAAElFTkSuQmCC'
        ];
        
        return $icons[$iconClass] ?? $icons['fa-cloud'];
    }
}