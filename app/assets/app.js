import { startStimulusApp } from '@symfony/stimulus-bundle';
import 'bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.min.css';
import 'tom-select/dist/css/tom-select.bootstrap5.css';
import './styles/app.css';
import './delete-handler.js';

// Start Stimulus application with automatic controller registration
const app = startStimulusApp();

// Disable verbose Stimulus debug logs in console
app.debug = false;
