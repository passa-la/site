import './pwa'
import Alpine from 'alpinejs'
import { finder } from './finder'

Alpine.data('finder', finder)
Alpine.start()
