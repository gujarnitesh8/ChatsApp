import {
    GET_NETWORK_STATUS_SUCCESS,
    GET_NETWORK_STATUS_FAILED
} from '../Types';


const INITIAL_STATE = {}

export default (state = INITIAL_STATE, action) => {
    switch (action.type) {

        case GET_NETWORK_STATUS_SUCCESS:
            return { ...state, isNetworkAvailable: action.payload }

        case GET_NETWORK_STATUS_FAILED:
            return { ...state, isNetworkAvailableFailed: true }

        default:
            return state;
    }
}