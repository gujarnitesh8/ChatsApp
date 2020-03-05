import {
    GET_NETWORK_STATUS_REQUEST

} from '../Types'


export const updateNetwork = (params) => {
    return {
        type: GET_NETWORK_STATUS_REQUEST,
        params
    };
}